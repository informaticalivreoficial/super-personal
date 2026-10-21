<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Integração com o gateway de pagamentos (API de Pedidos).
 *
 * Genérico o bastante para qualquer cobrança: recebe um "subject" (referência,
 * descrição e valor em centavos) e devolve o pedido normalizado. Hoje é usado
 * pela assinatura do treinador (`sub-{id}`) e pela cobrança online do aluno
 * (`pay-{id}`).
 *
 * Checkout transparente: o cartão é criptografado NO NAVEGADOR (SDK do gateway
 * + chave pública da conta) e só o hash chega ao servidor — o número do cartão
 * nunca passa por aqui. O Pix é criado no servidor e devolvido como QR Code.
 *
 * Todos os acessos à API passam por este service: facilita mock nos testes e
 * concentra o mapa de status do gateway.
 */
class PagBankService
{
    /** Timeout das chamadas ao gateway (segundos). */
    private const TIMEOUT = 20;

    /** Chave de cache da chave pública de criptografia de cartão. */
    private const PUBLIC_KEY_CACHE = 'pagbank.public_key';

    /**
     * Status da cobrança no gateway → status interno.
     * WAITING/IN_ANALYSIS = aguardando (Pix ainda não pago, análise de risco).
     */
    private const CHARGE_STATUS = [
        'PAID' => 'approved',
        'WAITING' => 'pending',
        'IN_ANALYSIS' => 'pending',
        'DECLINED' => 'rejected',
        'CANCELED' => 'rejected',
        'REFUNDED' => 'rejected',
        'IN_DISPUTE' => 'rejected',
    ];

    public function isConfigured(): bool
    {
        return filled(config('services.pagbank.token'));
    }

    /**
     * Chave pública usada pelo navegador para criptografar o cartão.
     * Prioriza o .env; senão consulta a conta (e memoiza no cache).
     */
    public function publicKey(): ?string
    {
        if (filled($configured = config('services.pagbank.public_key'))) {
            return (string) $configured;
        }

        if (filled($cached = Cache::get(self::PUBLIC_KEY_CACHE))) {
            return (string) $cached;
        }

        try {
            $response = $this->client()->get('public-keys/card');
        } catch (\Throwable $e) {
            Log::warning('Gateway: falha ao consultar a chave pública de cartão', ['error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful() || ! filled($key = $response->json('public_key'))) {
            Log::warning('Gateway: chave pública de cartão indisponível', ['status' => $response->status()]);

            return null;
        }

        Cache::put(self::PUBLIC_KEY_CACHE, $key, now()->addHours(12));

        return (string) $key;
    }

    /**
     * Cria um pedido com cartão de crédito (dados já criptografados).
     *
     * @param  array{reference: string, description: string, amount_cents: int}  $charge
     * @param  array{encrypted: string, holder: string, installments: int|string, cpf: string}  $card
     * @param  array{name?: string, email?: string, tax_id?: string}  $payer
     * @return array<string, mixed> pedido normalizado (ver normalizeOrder)
     *
     * @throws \RuntimeException quando o gateway não responde ou recusa o pedido.
     */
    public function createCardOrder(array $charge, array $card, array $payer): array
    {
        $request = [
            'reference_id' => $charge['reference'],
            'customer' => $this->customer($payer),
            'items' => [$this->item($charge)],
            'charges' => [[
                'reference_id' => "{$charge['reference']}-c1",
                'description' => $charge['description'],
                'amount' => ['value' => $charge['amount_cents'], 'currency' => 'BRL'],
                'payment_method' => [
                    'type' => 'CREDIT_CARD',
                    'installments' => max(1, (int) $card['installments']),
                    'capture' => true,
                    'card' => [
                        // Hash produzido no navegador — nunca o número do cartão.
                        'encrypted' => (string) $card['encrypted'],
                        'store' => false,
                    ],
                    'holder' => [
                        'name' => (string) $card['holder'],
                        'tax_id' => (string) $card['cpf'],
                    ],
                ],
            ]],
        ];

        return $this->normalizeOrder($this->post('orders', $request, $charge['reference'], 'cartão'));
    }

    /**
     * Cria um pedido com Pix (QR Code + copia e cola).
     *
     * O Pix é pedido pelo array `qr_codes` no topo do pedido. O caminho
     * `charges[].payment_method.pix` exige chave Pix cadastrada na conta e
     * responde `40010 PIX_KEY_NOT_FOUND` — confirmado no sandbox, onde o
     * `qr_codes` funciona (201) sem nenhuma chave cadastrada.
     *
     * @param  array{reference: string, description: string, amount_cents: int}  $charge
     * @param  array{name?: string, email?: string, tax_id?: string}  $payer
     * @return array<string, mixed> pedido normalizado (com qr_code_text/qr_code_image)
     *
     * @throws \RuntimeException quando o gateway não responde ou recusa o pedido.
     */
    public function createPixOrder(array $charge, array $payer): array
    {
        $request = [
            'reference_id' => $charge['reference'],
            'customer' => $this->customer($payer),
            'items' => [$this->item($charge)],
            'qr_codes' => [[
                'amount' => ['value' => $charge['amount_cents']],
                'expiration_date' => now()->addDay()->toIso8601String(),
            ]],
        ];

        return $this->normalizeOrder($this->post('orders', $request, $charge['reference'], 'Pix'));
    }

    /**
     * Consulta um pedido no gateway e devolve o formato normalizado.
     * Retorna null quando o pedido não existe ou o gateway falha (logado).
     */
    public function fetchOrder(string $orderId): ?array
    {
        if ($orderId === '') {
            return null;
        }

        try {
            $response = $this->client()->get('orders/'.$orderId);
        } catch (\Throwable $e) {
            Log::warning('Gateway: falha ao consultar pedido', ['order_id' => $orderId, 'error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Gateway: pedido indisponível', ['order_id' => $orderId, 'status' => $response->status()]);

            return null;
        }

        $data = $response->json();

        return is_array($data) && filled($data['id'] ?? null) ? $this->normalizeOrder($data) : null;
    }

    /**
     * Mensagem amigável (pt-BR) para pagamento recusado, pelo código do
     * gateway (mapa melhor esforço) com fallback pelo texto devolvido.
     */
    public function rejectionMessage(string $code = '', string $gatewayMessage = ''): string
    {
        $mapped = match ($code) {
            '20003' => 'Saldo ou limite insuficiente no cartão.',
            '10000' => 'Pagamento recusado por segurança. Tente outro cartão.',
            '10002', '20001' => 'Pagamento recusado pelo emissor do cartão. Contate seu banco.',
            '20019', '20158' => 'O banco emissor está indisponível no momento. Tente novamente em instantes.',
            '201' => 'Cartão inválido ou não aceito. Confira os dados e tente novamente.',
            default => null,
        };

        if ($mapped !== null) {
            return $mapped;
        }

        $text = mb_strtolower($gatewayMessage);

        return match (true) {
            str_contains($text, 'insufficient') => 'Saldo ou limite insuficiente no cartão.',
            str_contains($text, 'expired') => 'Cartão vencido.',
            str_contains($text, 'security code') => 'Código de segurança (CVV) inválido.',
            str_contains($text, 'invalid') => 'Dados do cartão inválidos. Confira e tente novamente.',
            default => 'Não foi possível aprovar o pagamento. Confira os dados ou use outro cartão.',
        };
    }

    /**
     * Normaliza o pedido/cobrança do gateway para o vocabulário interno.
     *
     * @param  array<string, mixed>  $order
     * @return array<string, mixed>
     */
    private function normalizeOrder(array $order): array
    {
        $charge = $order['charges'][0] ?? [];

        // Pix: `qr_codes[]` no topo do pedido (a resposta do pedido pago também
        // pode trazer a cobrança em `charges[].qr_code`).
        $qrCode = $charge['qr_code'] ?? ($order['qr_codes'][0] ?? []);
        $rawStatus = (string) ($charge['status'] ?? $order['status'] ?? '');

        $amountCents = (int) ($charge['amount']['value'] ?? $order['amount']['value'] ?? 0);

        if ($amountCents === 0 && is_array($order['items'] ?? null)) {
            foreach ($order['items'] as $item) {
                $amountCents += (int) ($item['unit_amount'] ?? 0) * (int) ($item['quantity'] ?? 1);
            }
        }

        return [
            'id' => (string) ($order['id'] ?? ''),
            'reference_id' => (string) ($order['reference_id'] ?? ''),
            'status' => self::CHARGE_STATUS[$rawStatus] ?? 'pending',
            'gateway_status' => $rawStatus,
            'charge_id' => (string) ($charge['id'] ?? ''),
            'amount_cents' => $amountCents,
            'code' => (string) ($charge['payment_response']['code'] ?? ''),
            'message' => (string) ($charge['payment_response']['message'] ?? ''),
            'qr_code_text' => (string) ($qrCode['text'] ?? ''),
            'qr_code_image' => $this->qrCodeImage($charge, $qrCode),
        ];
    }

    /** URL da imagem do QR Code (nos links da cobrança ou do próprio `qr_codes[]`). */
    private function qrCodeImage(array $charge, array $qrCode): string
    {
        $links = array_merge($charge['links'] ?? [], $qrCode['links'] ?? []);

        foreach ($links as $link) {
            if (($link['rel'] ?? '') === 'QRCODE.PNG' || ($link['media'] ?? '') === 'image/png') {
                return (string) ($link['href'] ?? '');
            }
        }

        return '';
    }

    /**
     * POST autenticado no gateway, com log do corpo de erro e exceção neutra
     * (a mensagem pode chegar ao cliente; o detalhe técnico fica no log).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws \RuntimeException
     */
    private function post(string $path, array $payload, string $reference, string $label): array
    {
        $payload = array_merge($payload, $this->notificationPayload());

        try {
            $response = $this->client()->post($path, $payload);
        } catch (\Throwable $e) {
            Log::error('Gateway: falha de comunicação ao criar o pedido', [
                'reference' => $reference,
                'label' => $label,
                'hint' => 'Verifique a rede e o token configurado.',
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Não foi possível processar o pagamento. Verifique sua conexão e tente novamente.');
        }

        if (! $response->successful()) {
            Log::error('Gateway: recusa ao criar o pedido', [
                'reference' => $reference,
                'label' => $label,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            throw new \RuntimeException($label === 'Pix'
                ? 'Não foi possível gerar o QR Code Pix ('.$response->status().'). Tente novamente ou contate o suporte.'
                : 'Não foi possível processar o pagamento ('.$response->status().'). Verifique os dados do cartão e tente novamente.');
        }

        return (array) $response->json();
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl((string) config('services.pagbank.base_url'))
            ->withToken((string) config('services.pagbank.token'))
            ->acceptJson()
            ->asJson()
            ->timeout(self::TIMEOUT);
    }

    /**
     * URL de notificação do gateway. Só é enviada quando HTTPS: em
     * desenvolvimento a URL local não é alcançável de fora (o painel cobre
     * com o botão "Já efetuei o pagamento").
     *
     * @return array{notification_urls?: array<int, string>}
     */
    private function notificationPayload(): array
    {
        $url = $this->webhookUrl();

        return str_starts_with($url, 'https://') ? ['notification_urls' => [$url]] : [];
    }

    private function webhookUrl(): string
    {
        return (string) (config('services.pagbank.webhook_url') ?: route('webhooks.pagbank'));
    }

    /** @param  array{name?: string, email?: string, tax_id?: string}  $payer */
    private function customer(array $payer): array
    {
        $customer = array_filter([
            'name' => $payer['name'] ?? null,
            'email' => $payer['email'] ?? null,
            'tax_id' => $payer['tax_id'] ?? null,
        ], fn ($value) => filled($value));

        return $customer;
    }

    /**
     * @param  array{reference: string, description: string, amount_cents: int}  $charge
     * @return array<string, mixed>
     */
    private function item(array $charge): array
    {
        return [
            'reference_id' => $charge['reference'],
            'name' => $charge['description'],
            'quantity' => 1,
            'unit_amount' => $charge['amount_cents'],
        ];
    }
}
