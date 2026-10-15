<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

/**
 * Webhook do gateway de pagamentos — assinaturas do SaaS.
 *
 * O corpo da notificação NÃO é fonte de verdade: sempre reconsultamos o
 * pedido no gateway e aplicamos o estado real à assinatura (referência
 * `sub-{id}`). Por isso o endpoint não depende da assinatura do payload —
 * uma notificação forjada não consegue "aprovar" nada.
 */
class PagBankWebhookController extends Controller
{
    public function __invoke(Request $request, SubscriptionService $subscriptions)
    {
        $orderId = (string) ($request->input('id') ?? '');
        $reference = (string) ($request->input('reference_id') ?? '');

        // Notificação sem identificação (ex.: chamada de teste) → aceita e ignora.
        if ($orderId === '' && $reference === '') {
            return response()->json(['received' => true]);
        }

        $subscriptions->syncFromOrder($orderId, $reference);

        // Sempre 200: o gateway não precisa reenviar (estado é reconsultado).
        return response()->json(['received' => true]);
    }
}
