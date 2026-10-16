<?php

namespace Tests\Feature\Services;

use App\Enums\PaymentChannel;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Config;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\PagBankService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

/**
 * Cobrança do aluno: canal online x manual, quota mensal de manuais,
 * checkout Pix e aplicação do pagamento (taxa da plataforma + líquido).
 */
class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Gateway falso: só os métodos pedidos existem; o resto nunca é chamado.
     *
     * @param  array<string, mixed>  $stubs
     */
    private function gateway(array $stubs = []): PagBankService
    {
        $mock = Mockery::mock(PagBankService::class);
        $mock->shouldReceive('isConfigured')->andReturn($stubs['configured'] ?? true);

        foreach (['createPixOrder', 'createCardOrder', 'fetchOrder'] as $method) {
            if (! array_key_exists($method, $stubs)) {
                continue;
            }

            $expectation = $mock->shouldReceive($method);

            $stubs[$method] instanceof \Closure
                ? $expectation->andReturnUsing($stubs[$method])
                : $expectation->andReturn($stubs[$method]);
        }

        return $mock;
    }

    /**
     * @return array<string, mixed> pedido Pix normalizado (PagBankService)
     */
    private function pixOrder(string $reference, string $text = '0002010102122658BR.COM...'): array
    {
        return [
            'id' => 'ORDE_PIX',
            'reference_id' => $reference,
            'status' => 'pending',
            'gateway_status' => 'WAITING',
            'charge_id' => '',
            'code' => '',
            'message' => '',
            'qr_code_text' => $text,
            'qr_code_image' => 'https://gateway.test/qrcode/QRCO_1/png',
        ];
    }

    private function service(array $stubs = []): PaymentService
    {
        return new PaymentService($this->gateway($stubs));
    }

    public function test_store_defaults_to_the_manual_channel(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        $payment = $this->service()->store($student, [
            'description' => 'Mensalidade',
            'amount' => 200,
            'due_date' => now()->addDays(5)->toDateString(),
        ]);

        $this->assertSame(PaymentChannel::MANUAL, $payment->channel);
        $this->assertSame($teacher->id, $payment->teacher_id);
        $this->assertSame($student->id, $payment->student_id);
    }

    public function test_manual_quota_blocks_the_eleventh_charge_in_the_month(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        Payment::factory()->count(10)->forStudent($student)->create([
            'channel' => PaymentChannel::MANUAL,
        ]);

        try {
            $this->service()->store($student, [
                'description' => 'Mensalidade',
                'amount' => 100,
                'due_date' => now()->addDay()->toDateString(),
            ]);
            $this->fail('A 11ª cobrança manual do mês deveria ter sido bloqueada.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('cobranças manuais por mês', $e->errors()['channel'][0]);
        }

        $this->assertDatabaseCount('payments', 10);
    }

    public function test_online_charges_do_not_count_toward_the_manual_quota(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        Payment::factory()->count(10)->forStudent($student)->create([
            'channel' => PaymentChannel::MANUAL,
        ]);

        $payment = $this->service()->store($student, [
            'description' => 'Mensalidade online',
            'amount' => 100,
            'channel' => PaymentChannel::ONLINE->value,
            'due_date' => now()->addDay()->toDateString(),
        ]);

        $this->assertSame(PaymentChannel::ONLINE, $payment->channel);
    }

    public function test_manual_quota_resets_next_month(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        $payments = Payment::factory()->count(10)->forStudent($student)->create([
            'channel' => PaymentChannel::MANUAL,
        ]);

        // As 10 são do mês passado → não contam para o mês atual.
        foreach ($payments as $payment) {
            $payment->forceFill(['created_at' => now()->subMonthNoOverflow()])->save();
        }

        $created = $this->service()->store($student, [
            'description' => 'Mensalidade',
            'amount' => 100,
            'due_date' => now()->addDay()->toDateString(),
        ]);

        $this->assertTrue($created->exists);
    }

    public function test_checkout_pix_generates_qr_and_stores_the_gateway_order(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();
        $payment = Payment::factory()->online()->forStudent($student)->create(['amount' => 200]);

        $service = $this->service([
            'createPixOrder' => function (array $charge, array $payer) use ($payment, $student) {
                $this->assertSame('pay-'.$payment->id, $charge['reference']);
                $this->assertSame(20000, $charge['amount_cents']);
                $this->assertSame($student->name, $payer['name']);
                $this->assertSame(preg_replace('/\D/', '', $student->document), $payer['tax_id']);

                return $this->pixOrder($charge['reference']);
            },
        ]);

        $result = $service->checkoutPix($payment);

        $this->assertSame('pending', $result['status']);
        $this->assertSame('0002010102122658BR.COM...', $result['qr_code']);

        $payment->refresh();
        $this->assertSame('ORDE_PIX', $payment->gateway_order_id);
        $this->assertSame(Payment::GATEWAY_PAGBANK, $payment->gateway);
        $this->assertSame(PaymentMethod::PIX, $payment->payment_method);
    }

    public function test_checkout_pix_accepts_the_cpf_from_the_request(): void
    {
        $student = Student::factory()->create(['document' => null]);
        $payment = Payment::factory()->online()->forStudent($student)->create(['amount' => 150]);

        $service = $this->service([
            'createPixOrder' => function (array $charge, array $payer) {
                $this->assertSame('12345678900', $payer['tax_id']);

                return $this->pixOrder($charge['reference']);
            },
        ]);

        $service->checkoutPix($payment, ['cpf' => '123.456.789-00']);

        $this->assertSame('ORDE_PIX', $payment->fresh()->gateway_order_id);
    }

    public function test_checkout_pix_rejects_manual_charges(): void
    {
        $student = Student::factory()->create();
        $payment = Payment::factory()->forStudent($student)->create();

        $this->expectException(ValidationException::class);

        $this->service()->checkoutPix($payment);
    }

    public function test_checkout_pix_requires_the_payer_cpf_before_the_gateway(): void
    {
        $student = Student::factory()->create(['document' => null]);
        $payment = Payment::factory()->online()->forStudent($student)->create();

        $gateway = Mockery::mock(PagBankService::class);
        $gateway->shouldReceive('isConfigured')->andReturn(true);
        $gateway->shouldNotReceive('createPixOrder');

        $this->expectException(ValidationException::class);

        try {
            (new PaymentService($gateway))->checkoutPix($payment);
        } finally {
            $this->assertNull($payment->fresh()->gateway_order_id);
        }
    }

    public function test_apply_order_paid_marks_payment_and_applies_the_platform_fee(): void
    {
        $student = Student::factory()->create();
        $payment = Payment::factory()->online()->forStudent($student)->create(['amount' => 200]);

        $result = $this->service()->applyOrder([
            'id' => 'ORDE_OK',
            'reference_id' => 'pay-'.$payment->id,
            'status' => 'approved',
            'gateway_status' => 'PAID',
        ]);

        $this->assertNotNull($result);

        $payment->refresh();
        $this->assertSame(PaymentStatus::PAID, $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame(10.0, (float) $payment->fee_amount);   // 5% de 200
        $this->assertSame(190.0, (float) $payment->net_amount);
        $this->assertSame('ORDE_OK', $payment->gateway_order_id);
    }

    public function test_apply_order_uses_the_configured_fee_percent(): void
    {
        Config::query()->create(['app_name' => 'Teste', 'fee_percent' => 10]);

        $student = Student::factory()->create();
        $payment = Payment::factory()->online()->forStudent($student)->create(['amount' => 200]);

        $this->service()->applyOrder([
            'id' => 'ORDE_OK',
            'reference_id' => 'pay-'.$payment->id,
            'status' => 'approved',
            'gateway_status' => 'PAID',
        ]);

        $payment->refresh();
        $this->assertSame(20.0, (float) $payment->fee_amount);   // 10% de 200
        $this->assertSame(180.0, (float) $payment->net_amount);
    }

    public function test_apply_order_reverts_a_refunded_payment(): void
    {
        $student = Student::factory()->create();
        $payment = Payment::factory()->online()->forStudent($student)->create([
            'amount' => 200,
            'status' => PaymentStatus::PAID,
            'paid_at' => now(),
            'fee_amount' => 10,
            'net_amount' => 190,
            'gateway_order_id' => 'ORDE_REF',
        ]);

        $this->service()->applyOrder([
            'id' => 'ORDE_REF',
            'reference_id' => 'pay-'.$payment->id,
            'status' => 'rejected',
            'gateway_status' => 'REFUNDED',
        ]);

        $payment->refresh();
        $this->assertSame(PaymentStatus::CANCELLED, $payment->status);
        $this->assertNull($payment->fee_amount);
        $this->assertNull($payment->net_amount);
        $this->assertNull($payment->paid_at);
    }

    public function test_apply_order_ignores_unknown_references(): void
    {
        $this->assertNull($this->service()->applyOrder([
            'reference_id' => 'pay-99999',
            'status' => 'approved',
            'gateway_status' => 'PAID',
        ]));
    }

    public function test_sync_from_order_uses_the_reference_when_the_id_is_missing(): void
    {
        $student = Student::factory()->create();
        $payment = Payment::factory()->online()->forStudent($student)->create([
            'amount' => 200,
            'gateway_order_id' => 'ORDE_SYNC',
        ]);

        $service = $this->service([
            'fetchOrder' => [
                'id' => 'ORDE_SYNC',
                'reference_id' => 'pay-'.$payment->id,
                'status' => 'approved',
                'gateway_status' => 'PAID',
            ],
        ]);

        $result = $service->syncFromOrder('', 'pay-'.$payment->id);

        $this->assertNotNull($result);
        $this->assertSame(PaymentStatus::PAID, $payment->fresh()->status);
    }
}
