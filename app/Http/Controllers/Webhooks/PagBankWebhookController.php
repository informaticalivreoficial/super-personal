<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Webhook do gateway de pagamentos.
 *
 * O corpo da notificação NÃO é fonte de verdade: sempre reconsultamos o
 * pedido no gateway e aplicamos o estado real. A referência é a cobrança do
 * aluno (`pay-{id}`) — a plataforma não cobra assinatura do treinador (a
 * receita é a comissão retida em cada cobrança online). Por isso uma
 * notificação forjada não consegue "aprovar" nada.
 *
 * Endurecimento:
 * - somente POST (rota);
 * - assinatura `X-Authenticity-Token` = SHA-256 de `{token}-{corpo bruto}`;
 * - allowlist opcional de IPs do gateway;
 * - idempotência por hash do corpo (o gateway pode reenviar o mesmo evento).
 */
class PagBankWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentService $payments)
    {
        if (! $this->isFromAllowedIp($request)) {
            Log::warning('Gateway: webhook de IP não autorizado', ['ip' => $request->ip()]);

            return response()->json(['message' => 'Origem não autorizada.'], 403);
        }

        if (! $this->hasValidSignature($request)) {
            Log::warning('Gateway: webhook com assinatura inválida', ['ip' => $request->ip()]);

            return response()->json(['message' => 'Assinatura inválida.'], 401);
        }

        $orderId = (string) ($request->input('id') ?? '');
        $reference = (string) ($request->input('reference_id') ?? '');

        // Notificação sem identificação (ex.: chamada de teste) → aceita e ignora.
        if ($orderId === '' && $reference === '') {
            return response()->json(['received' => true]);
        }

        // Idempotência: o mesmo corpo não é processado duas vezes.
        $eventKey = 'pagbank:webhook:'.hash('sha256', $request->getContent());

        if (! Cache::add($eventKey, true, now()->addHour())) {
            return response()->json(['received' => true, 'duplicated' => true]);
        }

        $payments->syncFromOrder($orderId, $reference);

        // Sempre 200: o gateway não precisa reenviar (estado é reconsultado).
        return response()->json(['received' => true]);
    }

    /**
     * Valida `X-Authenticity-Token` contra o token da conta.
     *
     * Sem token configurado, aceita apenas fora de produção (desenvolvimento e
     * testes) — em produção o webhook fica fechado até a configuração existir.
     */
    private function hasValidSignature(Request $request): bool
    {
        $token = (string) config('services.pagbank.webhook_token');

        if ($token === '') {
            if (app()->environment('production')) {
                Log::error('Gateway: PAGBANK_WEBHOOK_TOKEN ausente; webhook recusado em produção.');

                return false;
            }

            return true;
        }

        $received = mb_strtolower(trim((string) $request->header('X-Authenticity-Token')));
        $expected = hash('sha256', $token.'-'.$request->getContent());

        return $received !== '' && hash_equals($expected, $received);
    }

    /** Allowlist opcional (`PAGBANK_WEBHOOK_IPS`), separada por vírgula. */
    private function isFromAllowedIp(Request $request): bool
    {
        $allowed = array_filter(array_map('trim', explode(',', (string) config('services.pagbank.webhook_ips'))));

        return $allowed === [] || in_array((string) $request->ip(), $allowed, true);
    }
}
