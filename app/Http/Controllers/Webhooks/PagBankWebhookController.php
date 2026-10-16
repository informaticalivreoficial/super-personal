<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

/**
 * Webhook do gateway de pagamentos.
 *
 * O corpo da notificação NÃO é fonte de verdade: sempre reconsultamos o
 * pedido no gateway e aplicamos o estado real. A referência decide o alvo:
 * `sub-{id}` = assinatura do treinador; `pay-{id}` = cobrança online do aluno.
 * Por isso uma notificação forjada não consegue "aprovar" nada.
 */
class PagBankWebhookController extends Controller
{
    public function __invoke(Request $request, SubscriptionService $subscriptions, PaymentService $payments)
    {
        $orderId = (string) ($request->input('id') ?? '');
        $reference = (string) ($request->input('reference_id') ?? '');

        // Notificação sem identificação (ex.: chamada de teste) → aceita e ignora.
        if ($orderId === '' && $reference === '') {
            return response()->json(['received' => true]);
        }

        if (str_starts_with($reference, 'pay-')) {
            $payments->syncFromOrder($orderId, $reference);
        } else {
            $subscriptions->syncFromOrder($orderId, $reference);
        }

        // Sempre 200: o gateway não precisa reenviar (estado é reconsultado).
        return response()->json(['received' => true]);
    }
}
