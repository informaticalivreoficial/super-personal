<?php

namespace Tests\Feature\Web;

use App\Enums\PaymentStatus;
use App\Models\Config;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\PagBankService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Webhook do gateway: o corpo da notificação nunca é fonte de verdade —
 * o controller reconsulta o pedido e aplica o estado real. Hoje o único
 * alvo é a cobrança do aluno (`pay-{id}`): a plataforma não cobra
 * assinatura do treinador.
 */
class PagBankWebhookTest extends TestCase
{
    use RefreshDatabase;

    /** Cobrança online pendente (referência `pay-{id}`). */
    private function pendingCharge(float $amount = 200): Payment
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        return Payment::factory()->forStudent($student)->online()->create([
            'amount' => $amount,
            'gateway' => Payment::GATEWAY_PAGBANK,
            'gateway_order_id' => 'ORDE_PIX',
            'status' => PaymentStatus::PENDING,
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function fakeOrder(Payment $payment, string $gatewayStatus, array $overrides = []): void
    {
        $this->mock(PagBankService::class, function (MockInterface $mock) use ($payment, $gatewayStatus, $overrides) {
            $mock->shouldReceive('fetchOrder')->andReturn(array_merge([
                'id' => 'ORDE_PIX',
                'reference_id' => $payment->externalReference(),
                'status' => match ($gatewayStatus) {
                    'PAID' => 'approved',
                    'DECLINED' => 'rejected',
                    default => 'pending',
                },
                'gateway_status' => $gatewayStatus,
                'charge_id' => 'CHAR_1',
                'code' => $gatewayStatus === 'DECLINED' ? '20003' : '',
                'message' => '',
                'qr_code_text' => '',
                'qr_code_image' => '',
            ], $overrides));
        });
    }

    public function test_paid_order_marks_the_charge_as_paid_with_the_platform_fee(): void
    {
        $payment = $this->pendingCharge(200);
        $this->fakeOrder($payment, 'PAID');

        $this->postJson(route('webhooks.pagbank'), ['id' => 'ORDE_PIX'])->assertOk();

        $payment->refresh();

        $this->assertSame(PaymentStatus::PAID, $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame('PAID', $payment->gateway_status);
        // Comissão de 5% (padrão): taxa 10 e líquido 190 para o treinador.
        $this->assertSame(10.0, (float) $payment->fee_amount);
        $this->assertSame(190.0, (float) $payment->net_amount);
        $this->assertNull($payment->payout_id);
    }

    public function test_fee_follows_the_configured_percentage(): void
    {
        Config::query()->create(['app_name' => 'Teste', 'fee_percent' => 8]);

        $payment = $this->pendingCharge(300);
        $this->fakeOrder($payment, 'PAID');

        $this->postJson(route('webhooks.pagbank'), ['id' => 'ORDE_PIX'])->assertOk();

        $payment->refresh();

        $this->assertSame(24.0, (float) $payment->fee_amount);
        $this->assertSame(276.0, (float) $payment->net_amount);
    }

    public function test_waiting_order_only_records_the_gateway_status(): void
    {
        $payment = $this->pendingCharge();
        $this->fakeOrder($payment, 'WAITING');

        $this->postJson(route('webhooks.pagbank'), ['id' => 'ORDE_PIX'])->assertOk();

        $payment->refresh();

        $this->assertSame(PaymentStatus::PENDING, $payment->status);
        $this->assertSame('WAITING', $payment->gateway_status);
        $this->assertNull($payment->fee_amount);
    }

    public function test_refund_of_a_paid_charge_reverses_it(): void
    {
        $payment = $this->pendingCharge(200);
        $payment->update([
            'status' => PaymentStatus::PAID,
            'paid_at' => now()->subDay(),
            'fee_amount' => 10,
            'net_amount' => 190,
        ]);

        $this->fakeOrder($payment, 'REFUNDED');

        $this->postJson(route('webhooks.pagbank'), ['id' => 'ORDE_PIX'])->assertOk();

        $payment->refresh();

        $this->assertSame(PaymentStatus::CANCELLED, $payment->status);
        $this->assertNull($payment->paid_at);
        $this->assertNull($payment->fee_amount);
        $this->assertNull($payment->net_amount);
    }

    public function test_refund_of_a_charge_that_was_never_paid_is_ignored(): void
    {
        $payment = $this->pendingCharge();
        $this->fakeOrder($payment, 'REFUNDED');

        $this->postJson(route('webhooks.pagbank'), ['id' => 'ORDE_PIX'])->assertOk();

        $this->assertSame(PaymentStatus::PENDING, $payment->refresh()->status);
    }

    public function test_notification_without_order_id_uses_stored_link(): void
    {
        $payment = $this->pendingCharge();
        $this->fakeOrder($payment, 'PAID');

        // Alguns eventos trazem só a referência (`pay-{id}`).
        $this->postJson(route('webhooks.pagbank'), ['reference_id' => $payment->externalReference()])
            ->assertOk();

        $this->assertSame(PaymentStatus::PAID, $payment->refresh()->status);
    }

    public function test_unknown_reference_is_ignored(): void
    {
        $payment = $this->pendingCharge();

        $this->mock(PagBankService::class, function (MockInterface $mock) {
            $mock->shouldReceive('fetchOrder')->andReturn([
                'id' => 'ORDE_FOREIGN',
                'reference_id' => 'pay-999999',
                'status' => 'approved',
                'gateway_status' => 'PAID',
                'charge_id' => 'CHAR',
                'code' => '',
                'message' => '',
                'qr_code_text' => '',
                'qr_code_image' => '',
            ]);
        });

        $this->postJson(route('webhooks.pagbank'), ['id' => 'ORDE_FOREIGN'])->assertOk();

        $this->assertSame(PaymentStatus::PENDING, $payment->refresh()->status);
    }

    public function test_notification_without_identification_is_ignored(): void
    {
        $this->mock(PagBankService::class, function (MockInterface $mock) {
            $mock->shouldReceive('fetchOrder')->never();
        });

        $this->postJson(route('webhooks.pagbank'), [])->assertOk();
    }

    public function test_get_notification_is_accepted(): void
    {
        $payment = $this->pendingCharge();
        $this->fakeOrder($payment, 'PAID');

        $this->get(route('webhooks.pagbank').'?id=ORDE_PIX')->assertOk();

        $this->assertSame(PaymentStatus::PAID, $payment->refresh()->status);
    }

    public function test_route_is_exempt_from_csrf(): void
    {
        $payment = $this->pendingCharge();
        $this->fakeOrder($payment, 'PAID');

        // Sem token CSRF: chamada server-to-server do gateway.
        $this->post(route('webhooks.pagbank'), ['id' => 'ORDE_PIX'])->assertOk();

        $this->assertSame(PaymentStatus::PAID, $payment->refresh()->status);
    }
}
