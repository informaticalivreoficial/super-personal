<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\MercadoPagoService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

/**
 * Webhook do Mercado Pago (Checkout Pro) — assinaturas do SaaS.
 * Pagamento aprovado ativa a assinatura; rejeitado marca "em atraso".
 */
class MercadoPagoWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        MercadoPagoService $mercadopago,
        SubscriptionService $subscriptions,
    ) {
        $dataId = (string) ($request->input('data.id') ?? $request->input('id') ?? '');

        // Notificação sem id de pagamento (ex.: chamada de teste) → aceita e ignora.
        if ($dataId === '') {
            return response()->json(['received' => true]);
        }

        $valid = $mercadopago->isValidSignature(
            $request->header('x-signature'),
            $request->header('x-request-id'),
            $dataId,
        );

        abort_unless($valid, 403, 'Assinatura de webhook inválida.');

        $payment = $mercadopago->fetchPayment($dataId);

        // Gateway indisponível/rate limit → 200 para o MP não marcar como falho.
        if (! $payment) {
            return response()->json(['received' => true]);
        }

        $status = $payment['status'] ?? null;

        if ($status === 'approved') {
            $subscriptions->activateFromPayment($payment);
        } elseif (in_array($status, ['rejected', 'cancelled', 'refunded', 'chargeback'], true)) {
            $subscriptions->markPaymentRejected($payment);
        }

        // pending/in_process/in_mediation: aguarda próximo evento.

        return response()->json(['received' => true]);
    }
}
