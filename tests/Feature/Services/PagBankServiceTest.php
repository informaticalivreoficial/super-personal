<?php

namespace Tests\Feature\Services;

use App\Services\PagBankService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * PagBankService contra a API de Pedidos. Todas as chamadas são interceptadas
 * por Http::fake — nenhum request sai de verdade.
 *
 * O service é genérico: recebe um "subject" (referência, descrição e valor em
 * centavos). Hoje o único uso é a cobrança online do aluno (`pay-{id}`).
 */
class PagBankServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.pagbank.token' => 'token-teste',
            'services.pagbank.public_key' => null,
            'services.pagbank.sandbox' => true,
            'services.pagbank.base_url' => 'https://sandbox.api.pagseguro.com',
            'services.pagbank.webhook_url' => 'https://app.test/webhooks/pagbank',
        ]);
    }

    /**
     * @return array{reference: string, description: string, amount_cents: int}
     */
    private function charge(float $amount = 49.90, string $reference = 'pay-1'): array
    {
        return [
            'reference' => $reference,
            'description' => 'Cobrança de treino',
            'amount_cents' => (int) round($amount * 100),
        ];
    }

    public function test_is_configured_follows_the_token(): void
    {
        $this->assertTrue(app(PagBankService::class)->isConfigured());

        config(['services.pagbank.token' => null]);

        $this->assertFalse(app(PagBankService::class)->isConfigured());
    }

    public function test_public_key_prefers_the_configured_value(): void
    {
        Http::fake();

        config(['services.pagbank.public_key' => 'PUB-DO-ENV']);

        $this->assertSame('PUB-DO-ENV', app(PagBankService::class)->publicKey());
        Http::assertNothingSent();
    }

    public function test_public_key_is_fetched_from_the_account_and_cached(): void
    {
        Http::fake([
            '*/public-keys/card' => Http::response(['public_key' => 'CHAVE-DA-CONTA'], 200),
        ]);

        $service = app(PagBankService::class);

        $this->assertSame('CHAVE-DA-CONTA', $service->publicKey());
        // Segunda chamada usa o cache (uma única requisição ao gateway).
        $this->assertSame('CHAVE-DA-CONTA', $service->publicKey());

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://sandbox.api.pagseguro.com/public-keys/card'
            && $request->hasHeader('Authorization', 'Bearer token-teste'));
    }

    public function test_public_key_returns_null_when_the_account_refuses(): void
    {
        Http::fake([
            '*/public-keys/card' => Http::response(['message' => 'unauthorized'], 401),
        ]);

        $this->assertNull(app(PagBankService::class)->publicKey());
    }

    public function test_create_card_order_posts_encrypted_card_and_holder(): void
    {
        Http::fake([
            '*/orders' => Http::response([
                'id' => 'ORDE_1',
                'reference_id' => 'pay-1',
                'charges' => [[
                    'id' => 'CHAR_1',
                    'status' => 'PAID',
                    'payment_response' => ['code' => '0', 'message' => 'ACCEPTED'],
                ]],
            ], 201),
        ]);

        $order = app(PagBankService::class)->createCardOrder($this->charge(49.90), [
            'encrypted' => 'hash-criptografado',
            'holder' => 'Fulano de Tal',
            'installments' => 3,
            'cpf' => '12345678900',
        ], [
            'name' => 'Treinador Teste',
            'email' => 'treinador@teste.com',
            'tax_id' => '12345678900',
        ]);

        Http::assertSent(function (Request $request) {
            $charge = $request['charges'][0];

            return $request->url() === 'https://sandbox.api.pagseguro.com/orders'
                && $request['reference_id'] === 'pay-1'
                && $request['customer']['email'] === 'treinador@teste.com'
                && $request['items'][0]['unit_amount'] === 4990 // centavos
                && $charge['payment_method']['type'] === 'CREDIT_CARD'
                && $charge['payment_method']['installments'] === 3
                && $charge['payment_method']['capture'] === true
                && $charge['payment_method']['card']['encrypted'] === 'hash-criptografado'
                && $charge['payment_method']['holder']['name'] === 'Fulano de Tal'
                && $charge['payment_method']['holder']['tax_id'] === '12345678900'
                && $charge['amount']['value'] === 4990
                && $charge['amount']['currency'] === 'BRL'
                && $request['notification_urls'] === ['https://app.test/webhooks/pagbank'];
        });

        $this->assertSame('ORDE_1', $order['id']);
        $this->assertSame('approved', $order['status']);
        $this->assertSame('PAID', $order['gateway_status']);
        $this->assertSame('pay-1', $order['reference_id']);
    }

    public function test_card_order_omits_notification_urls_on_http(): void
    {
        // Em desenvolvimento a URL local não é alcançável de fora.
        config(['services.pagbank.webhook_url' => null, 'app.url' => 'http://laravel.test']);

        Http::fake(['*/orders' => Http::response(['id' => 'ORDE_2', 'charges' => [['status' => 'PAID']]], 201)]);

        app(PagBankService::class)->createCardOrder($this->charge(), [
            'encrypted' => 'hash',
            'holder' => 'Fulano',
            'installments' => 1,
            'cpf' => '12345678900',
        ], ['email' => 'treinador@teste.com']);

        Http::assertSent(fn (Request $request) => ! isset($request['notification_urls']));
    }

    public function test_create_card_order_throws_neutral_message_on_gateway_error(): void
    {
        Http::fake([
            '*/orders' => Http::response([
                'error_messages' => [['code' => '40002', 'description' => 'invalid_parameter']],
            ], 400),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Não foi possível processar o pagamento (400)');

        app(PagBankService::class)->createCardOrder($this->charge(), [
            'encrypted' => 'hash',
            'holder' => 'Fulano',
            'installments' => 1,
            'cpf' => '12345678900',
        ], ['email' => 'treinador@teste.com']);
    }

    public function test_create_pix_order_posts_qr_codes_and_returns_copy_paste(): void
    {
        // Resposta real do sandbox para POST /orders com qr_codes.
        Http::fake([
            '*/orders' => Http::response([
                'id' => 'ORDE_PIX',
                'reference_id' => 'pay-1',
                'qr_codes' => [[
                    'id' => 'QRCO_1',
                    'text' => '0002010102122658...',
                    'links' => [
                        ['rel' => 'QRCODE.PNG', 'href' => 'https://sandbox.api.pagseguro.com/qrcode/QRCO_1/png'],
                        ['rel' => 'QRCODE.BASE64', 'href' => 'https://sandbox.api.pagseguro.com/qrcode/QRCO_1/base64'],
                    ],
                ]],
                'links' => [['rel' => 'SELF', 'href' => 'https://sandbox.api.pagseguro.com/orders/ORDE_PIX']],
            ], 201),
        ]);

        $order = app(PagBankService::class)->createPixOrder($this->charge(99.90), [
            'name' => 'Treinador Teste',
            'email' => 'treinador@teste.com',
        ]);

        Http::assertSent(function (Request $request) {
            $qrCode = $request['qr_codes'][0];

            return $request->url() === 'https://sandbox.api.pagseguro.com/orders'
                && $request['reference_id'] === 'pay-1'
                && $qrCode['amount']['value'] === 9990 // centavos
                && isset($qrCode['expiration_date'])
                // Regressão: `charges[].payment_method.pix` exige chave Pix na
                // conta e responde 40010 PIX_KEY_NOT_FOUND.
                && ! isset($request['charges']);
        });

        $this->assertSame('pending', $order['status']);
        $this->assertSame('0002010102122658...', $order['qr_code_text']);
        $this->assertSame('https://sandbox.api.pagseguro.com/qrcode/QRCO_1/png', $order['qr_code_image']);
    }

    public function test_pix_order_also_reads_the_qr_code_from_a_charge(): void
    {
        // Cobrança com o QR dentro de `charges[].qr_code` (defensivo: o pedido
        // pago pode voltar com a cobrança, não só com o `qr_codes[]`).
        Http::fake([
            '*/orders' => Http::response([
                'id' => 'ORDE_COBRANCA',
                'reference_id' => 'pay-1',
                'charges' => [[
                    'id' => 'CHAR_PIX',
                    'status' => 'WAITING',
                    'qr_code' => ['id' => 'QRCO_2', 'text' => '00020126580014BR.COM...'],
                    'links' => [['rel' => 'QRCODE.PNG', 'href' => 'https://sandbox.api.pagseguro.com/qrcode/QRCO_2/png']],
                ]],
            ], 201),
        ]);

        $order = app(PagBankService::class)->createPixOrder($this->charge(), ['email' => 'treinador@teste.com']);

        $this->assertSame('00020126580014BR.COM...', $order['qr_code_text']);
        $this->assertSame('https://sandbox.api.pagseguro.com/qrcode/QRCO_2/png', $order['qr_code_image']);
    }

    public function test_pix_order_without_copy_paste_text_is_detected(): void
    {
        // Sem `text` não há QR para mostrar: o service devolve vazio e o
        // PaymentService trata como falha.
        Http::fake(['*/orders' => Http::response(['id' => 'ORDE_VAZIO', 'reference_id' => 'pay-1'], 201)]);

        $order = app(PagBankService::class)->createPixOrder($this->charge(), ['email' => 'treinador@teste.com']);

        $this->assertSame('', $order['qr_code_text']);
    }

    public function test_fetch_order_normalizes_the_gateway_status(): void
    {
        Http::fake([
            '*/orders/ORDE_9' => Http::response([
                'id' => 'ORDE_9',
                'reference_id' => 'pay-9',
                'charges' => [[
                    'id' => 'CHAR_9',
                    'status' => 'DECLINED',
                    'payment_response' => ['code' => '20003', 'message' => 'Insufficient funds'],
                ]],
            ], 200),
        ]);

        $order = app(PagBankService::class)->fetchOrder('ORDE_9');

        $this->assertSame('rejected', $order['status']);
        $this->assertSame('20003', $order['code']);
        $this->assertSame('Insufficient funds', $order['message']);
    }

    public function test_fetch_order_returns_null_when_gateway_fails(): void
    {
        Http::fake(['*/orders/ORDE_X' => Http::response(['message' => 'not found'], 404)]);

        $this->assertNull(app(PagBankService::class)->fetchOrder('ORDE_X'));
    }

    public function test_rejection_messages_cover_common_reasons(): void
    {
        $service = app(PagBankService::class);

        $this->assertSame('Saldo ou limite insuficiente no cartão.', $service->rejectionMessage('20003'));
        $this->assertSame('Pagamento recusado por segurança. Tente outro cartão.', $service->rejectionMessage('10000'));
        $this->assertSame('Cartão inválido ou não aceito. Confira os dados e tente novamente.', $service->rejectionMessage('201'));

        // Código desconhecido: cai no texto devolvido pelo gateway.
        $this->assertSame('Cartão vencido.', $service->rejectionMessage('99999', 'Expired card'));

        // Sem código nem texto reconhecível → fallback genérico (nunca vaza técnico).
        $this->assertSame(
            'Não foi possível aprovar o pagamento. Confira os dados ou use outro cartão.',
            $service->rejectionMessage('99999', 'some unknown reason')
        );
    }
}
