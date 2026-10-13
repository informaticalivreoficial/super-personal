<?php

namespace App\Services;

use App\Models\Subscription;
use Illuminate\Support\Facades\Log;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Resources\Payment;
use MercadoPago\Resources\Preference;

/**
 * Integração com Mercado Pago (Checkout Pro) para assinaturas do SaaS.
 * Todos os acessos à API passam por aqui — facilita mock nos testes.
 */
class MercadoPagoService
{
    public function __construct()
    {
        MercadoPagoConfig::setAccessToken((string) config('services.mercadopago.access_token'));
    }

    public function isConfigured(): bool
    {
        return filled(config('services.mercadopago.access_token'));
    }

    /**
     * Cria a preferência de pagamento (Checkout Pro) da assinatura
     * e retorna a URL de checkout (sandbox em ambiente de teste).
     */
    public function createCheckoutPreference(Subscription $subscription): string
    {
        $subscription->loadMissing('plan');

        $preference = new Preference(MercadoPagoConfig::getDefault());

        $preference->items = [[
            'title' => sprintf('%s — assinatura %s', $subscription->plan?->name ?? 'Plano', $subscription->period?->value ?? ''),
            'unit_price' => (float) $subscription->amount,
            'quantity' => 1,
            'currency_id' => 'BRL',
        ]];

        $preference->external_reference = $subscription->externalReference();
        $preference->notification_url = $this->webhookUrl();
        $preference->auto_return = 'approved';
        $preference->back_urls = [
            'success' => route('subscription.show'),
            'pending' => route('subscription.show'),
            'failure' => route('subscription.show'),
        ];
        $preference->metadata = [
            'teacher_id' => $subscription->teacher_id,
            'subscription_id' => $subscription->id,
        ];

        $preference->save();

        return (string) ($preference->sandbox_init_point ?: $preference->init_point);
    }

    /**
     * Busca um pagamento no gateway e normaliza os campos que usamos.
     * Retorna null se não encontrar (ou erro de API — logado).
     */
    public function fetchPayment(string $paymentId): ?array
    {
        try {
            $payment = Payment::find_by_id($paymentId, MercadoPagoConfig::getDefault());
        } catch (\Throwable $e) {
            Log::warning('Mercado Pago: falha ao consultar pagamento', ['payment_id' => $paymentId, 'error' => $e->getMessage()]);

            return null;
        }

        if (! $payment || ! $payment->id) {
            return null;
        }

        return [
            'id' => (string) $payment->id,
            'status' => (string) $payment->status,
            'external_reference' => (string) $payment->external_reference,
            'transaction_amount' => (float) $payment->transaction_amount,
        ];
    }

    /**
     * Valida a assinatura HMAC-SHA256 enviada pelo Mercado Pago
     * no header x-signature (ts=...,v1=...). Compara só quando há
     * secret configurado; sem secret, aceita (dev/local).
     */
    public function isValidSignature(?string $signature, ?string $requestId, ?string $dataId): bool
    {
        $secret = (string) config('services.mercadopago.webhook_secret');

        if ($secret === '' || $signature === null || $signature === '') {
            return true;
        }

        $parts = [];
        foreach (explode(',', $signature) as $piece) {
            [$key, $value] = array_pad(explode('=', trim($piece), 2), 2, null);
            $parts[$key] = $value;
        }

        $ts = $parts['ts'] ?? null;
        $v1 = $parts['v1'] ?? null;

        if (! $ts || ! $v1) {
            return false;
        }

        $manifest = sprintf('id:%s;request-id:%s;ts:%s;', (string) $dataId, (string) $requestId, $ts);

        return hash_hmac('sha256', $manifest, $secret) === $v1;
    }

    private function webhookUrl(): string
    {
        return (string) (config('services.mercadopago.webhook_url') ?: route('webhooks.mercadopago'));
    }
}
