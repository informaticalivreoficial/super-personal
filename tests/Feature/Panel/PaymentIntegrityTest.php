<?php

namespace Tests\Feature\Panel;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Livewire\Dashboard\Payments\PaymentIndex;
use App\Models\Config;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Student;
use App\Services\PaymentService;
use App\Services\PayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Integridade financeira: cobrança online só é paga pelo gateway; pagamentos
 * confirmados não são editáveis/excluíveis de forma a falsear taxa/repasse.
 */
class PaymentIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function student(): Student
    {
        return Student::factory()->create();
    }

    public function test_online_payment_cannot_be_created_as_paid_manually(): void
    {
        $student = $this->student();

        $this->expectException(ValidationException::class);

        app(PaymentService::class)->store($student, [
            'description' => 'Tentativa',
            'amount' => 200,
            'due_date' => now()->toDateString(),
            'channel' => PaymentChannel::ONLINE->value,
            'status' => PaymentStatus::PAID->value,
        ]);
    }

    public function test_manual_payment_can_still_be_created_as_paid(): void
    {
        $student = $this->student();

        $payment = app(PaymentService::class)->store($student, [
            'description' => 'Recebido por transferência',
            'amount' => 200,
            'due_date' => now()->toDateString(),
            'channel' => PaymentChannel::MANUAL->value,
            'status' => PaymentStatus::PAID->value,
        ]);

        $this->assertTrue($payment->isOnline() === false);
        $this->assertSame(PaymentStatus::PAID, $payment->status);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_pending_payment_cannot_be_switched_to_online_and_paid(): void
    {
        $payment = Payment::factory()->forStudent($this->student())->create();

        $this->expectException(ValidationException::class);

        app(PaymentService::class)->update($payment, [
            'channel' => PaymentChannel::ONLINE->value,
            'status' => PaymentStatus::PAID->value,
        ]);
    }

    public function test_paid_online_payment_blocks_amount_and_status_changes(): void
    {
        $payment = Payment::factory()->online()->paid()->forStudent($this->student())->create([
            'amount' => 200,
        ]);

        $service = app(PaymentService::class);

        try {
            $service->update($payment, ['amount' => 100]);
            $this->fail('Valor de cobrança online paga deveria ser bloqueado.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }

        try {
            $service->update($payment, ['status' => PaymentStatus::PENDING->value]);
            $this->fail('Status de cobrança online paga deveria ser bloqueado.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }

        $payment->refresh();
        $this->assertSame('200.00', $payment->amount);
        $this->assertSame(PaymentStatus::PAID, $payment->status);
    }

    public function test_paid_online_payment_allows_notes_without_losing_confirmation(): void
    {
        $payment = Payment::factory()->online()->paid()->forStudent($this->student())->create([
            'amount' => 200,
            'paid_at' => now()->subDays(2),
        ]);
        $paidAt = $payment->paid_at;

        app(PaymentService::class)->update($payment, ['notes' => 'Observação corrigida.']);

        $this->assertSame('Observação corrigida.', $payment->fresh()->notes);
        $this->assertSame(PaymentStatus::PAID, $payment->fresh()->status);
        $this->assertTrue($payment->fresh()->paid_at->equalTo($paidAt));
    }

    public function test_paid_payment_cannot_be_deleted_but_pending_can(): void
    {
        $student = $this->student();
        $paid = Payment::factory()->forStudent($student)->paid()->create();
        $pending = Payment::factory()->forStudent($student)->create();

        $service = app(PaymentService::class);

        try {
            $service->delete($paid);
            $this->fail('Cobrança paga não deveria ser excluída.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('payment', $e->errors());
        }

        $service->delete($pending);

        $this->assertDatabaseHas('payments', ['id' => $paid->id]);
        $this->assertDatabaseMissing('payments', ['id' => $pending->id]);
    }

    public function test_payment_in_payout_cannot_be_updated(): void
    {
        $payment = Payment::factory()->online()->paid()->forStudent($this->student())->create();
        $payout = Payout::factory()->create([
            'teacher_id' => $payment->teacher_id,
            'status' => PayoutStatus::REQUESTED,
        ]);
        $payment->forceFill(['payout_id' => $payout->id])->save();

        $this->expectException(ValidationException::class);

        app(PaymentService::class)->update($payment, ['notes' => 'Tentativa']);
    }

    public function test_index_does_not_mark_online_payment_as_paid(): void
    {
        $payment = Payment::factory()->online()->forStudent($this->student())->create();

        $this->actingAs($payment->teacher->user);

        Livewire::test(PaymentIndex::class)
            ->call('markAsPaid', $payment->id)
            ->assertDispatched('toast');

        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
    }

    public function test_gateway_cannot_confirm_a_manual_payment(): void
    {
        $payment = Payment::factory()->forStudent($this->student())->create();

        $result = app(PaymentService::class)->applyOrder([
            'id' => 'ORDE_MANUAL',
            'reference_id' => $payment->externalReference(),
            'status' => 'approved',
            'gateway_status' => 'PAID',
            'amount_cents' => 20000,
        ]);

        $this->assertNull($result);
        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
    }

    public function test_gateway_order_with_divergent_amount_does_not_mark_as_paid(): void
    {
        $payment = Payment::factory()->online()->forStudent($this->student())->create([
            'amount' => 200,
        ]);

        app(PaymentService::class)->applyOrder([
            'id' => 'ORDE_BARATO',
            'reference_id' => $payment->externalReference(),
            'status' => 'approved',
            'gateway_status' => 'PAID',
            'amount_cents' => 100,
        ]);

        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
        $this->assertNull($payment->fresh()->fee_amount);
    }

    public function test_repeated_approval_does_not_recalculate_fee_with_new_config(): void
    {
        $payment = Payment::factory()->online()->forStudent($this->student())->create([
            'amount' => 200,
        ]);
        $order = [
            'id' => 'ORDE_1',
            'reference_id' => $payment->externalReference(),
            'status' => 'approved',
            'gateway_status' => 'PAID',
            'amount_cents' => 20000,
        ];

        $service = app(PaymentService::class);
        $service->applyOrder($order);

        $fee = $payment->fresh()->fee_amount;

        Config::query()->first()?->update(['fee_percent' => 20]);
        Config::query()->update(['fee_percent' => 20]);

        $service->applyOrder($order);

        $this->assertSame($fee, $payment->fresh()->fee_amount);
        $this->assertSame('190.00', $payment->fresh()->net_amount);
    }

    public function test_paid_manual_payment_cannot_change_channel_but_can_go_back_to_pending(): void
    {
        $payment = Payment::factory()->forStudent($this->student())->paid()->create();
        $service = app(PaymentService::class);

        try {
            $service->update($payment, ['channel' => PaymentChannel::ONLINE->value]);
            $this->fail('Canal de cobrança paga deveria ser bloqueado.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('channel', $e->errors());
        }

        $service->update($payment, ['status' => PaymentStatus::PENDING->value]);

        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
        $this->assertNull($payment->fresh()->paid_at);
    }

    public function test_refund_of_payment_in_requested_payout_recalculates_the_payout(): void
    {
        $student = $this->student();
        $first = Payment::factory()->online()->paid()->forStudent($student)->create([
            'amount' => 200,
            'fee_amount' => 10,
            'net_amount' => 190,
        ]);
        $second = Payment::factory()->online()->paid()->forStudent($student)->create([
            'amount' => 100,
            'fee_amount' => 5,
            'net_amount' => 95,
        ]);

        $payout = Payout::factory()->create([
            'teacher_id' => $student->teacher_id,
            'status' => PayoutStatus::REQUESTED,
            'amount' => 300,
            'fee_total' => 15,
            'net_amount' => 285,
        ]);

        Payment::query()->whereIn('id', [$first->id, $second->id])->update(['payout_id' => $payout->id]);

        app(PaymentService::class)->applyOrder([
            'id' => 'ORDE_REFUND',
            'reference_id' => $first->externalReference(),
            'status' => 'refunded',
            'gateway_status' => 'REFUNDED',
        ]);

        $payout->refresh();

        $this->assertSame(PaymentStatus::CANCELLED, $first->fresh()->status);
        $this->assertNull($first->fresh()->payout_id);
        $this->assertSame(PayoutStatus::REQUESTED, $payout->status);
        $this->assertSame('100.00', $payout->amount);
        $this->assertSame('5.00', $payout->fee_total);
        $this->assertSame('95.00', $payout->net_amount);
    }

    public function test_refund_of_the_only_payment_cancels_the_requested_payout(): void
    {
        $payment = Payment::factory()->online()->paid()->forStudent($this->student())->create([
            'amount' => 200,
            'fee_amount' => 10,
            'net_amount' => 190,
        ]);
        $payout = Payout::factory()->create([
            'teacher_id' => $payment->teacher_id,
            'status' => PayoutStatus::REQUESTED,
            'amount' => 200,
            'fee_total' => 10,
            'net_amount' => 190,
        ]);
        $payment->forceFill(['payout_id' => $payout->id])->save();

        app(PaymentService::class)->applyOrder([
            'id' => 'ORDE_REFUND2',
            'reference_id' => $payment->externalReference(),
            'status' => 'refunded',
            'gateway_status' => 'REFUNDED',
        ]);

        $this->assertSame(PayoutStatus::CANCELLED, $payout->fresh()->status);
        $this->assertNull($payout->fresh()->paid_at);
        $this->assertNull($payment->fresh()->payout_id);
    }

    public function test_payout_cannot_be_paid_or_cancelled_twice(): void
    {
        $teacher = Student::factory()->create()->teacher;
        $payout = Payout::factory()->create([
            'teacher_id' => $teacher->id,
            'status' => PayoutStatus::PAID,
        ]);

        $service = app(PayoutService::class);

        foreach (['markPaid', 'cancel'] as $method) {
            try {
                $service->{$method}($payout);
                $this->fail("{$method} deveria recusar repasse já pago.");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('payout', $e->errors());
            }
        }
    }
}
