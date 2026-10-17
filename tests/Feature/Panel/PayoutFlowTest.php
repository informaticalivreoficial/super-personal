<?php

namespace Tests\Feature\Panel;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Livewire\Dashboard\Payments\PaymentForm;
use App\Livewire\Dashboard\Payouts\AdminPayoutIndex;
use App\Livewire\Dashboard\Payouts\PayoutIndex;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\PayoutService;
use Database\Seeders\ConfigTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Repasses ao treinador (líquido das cobranças online) e o canal de cobrança
 * no formulário do painel.
 */
class PayoutFlowTest extends TestCase
{
    use RefreshDatabase;

    /** Cobrança online já paga, com taxa/líquido (campos não-fillable). */
    private function paidOnlinePayment(Teacher $teacher, float $amount = 200): Payment
    {
        $student = Student::factory()->forTeacher($teacher)->create();
        $fee = round($amount * 0.05, 2);

        $payment = Payment::factory()->online()->forStudent($student)->create([
            'amount' => $amount,
            'status' => PaymentStatus::PAID,
            'paid_at' => now(),
        ]);

        $payment->forceFill([
            'fee_amount' => $fee,
            'net_amount' => round($amount - $fee, 2),
        ])->save();

        return $payment;
    }

    public function test_teacher_sees_the_balance_of_online_paid_charges(): void
    {
        $teacher = Teacher::factory()->create();
        $this->paidOnlinePayment($teacher, 200);

        $this->actingAs($teacher->user);

        Livewire::test(PayoutIndex::class)
            ->assertOk()
            ->assertSee('Saldo disponível')
            ->assertSee('R$ 190,00')
            ->assertSee('Solicitar repasse')
            ->assertSee('Cobranças aguardando repasse');
    }

    public function test_manual_or_unpaid_charges_do_not_enter_the_balance(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        // Manual paga e online pendente não geram saldo.
        Payment::factory()->forStudent($student)->create([
            'amount' => 100, 'status' => PaymentStatus::PAID, 'paid_at' => now(),
        ]);
        Payment::factory()->online()->forStudent($student)->create([
            'amount' => 100, 'status' => PaymentStatus::PENDING,
        ]);

        $this->actingAs($teacher->user);

        Livewire::test(PayoutIndex::class)
            ->assertOk()
            ->assertSee('Nenhuma cobrança aguardando repasse.');
    }

    public function test_balance_ignores_another_teachers_charges(): void
    {
        $teacher = Teacher::factory()->create();
        $other = Teacher::factory()->create();
        $this->paidOnlinePayment($other, 500);

        $this->actingAs($teacher->user);

        Livewire::test(PayoutIndex::class)
            ->assertOk()
            ->assertSee('Nenhuma cobrança aguardando repasse.');
    }

    public function test_teacher_requests_a_payout_grouping_the_charges(): void
    {
        $teacher = Teacher::factory()->create();
        $first = $this->paidOnlinePayment($teacher, 200);
        $second = $this->paidOnlinePayment($teacher, 100);

        $this->actingAs($teacher->user);

        Livewire::test(PayoutIndex::class)
            ->set('notes', 'Chave Pix do cadastro')
            ->call('requestPayout')
            ->assertDispatched('toast', fn ($name, $params) => $params[0]['type'] === 'success'
                && str_contains($params[0]['message'], 'Repasse solicitado'));

        $payout = Payout::firstOrFail();
        $this->assertSame($teacher->id, $payout->teacher_id);
        $this->assertSame(PayoutStatus::REQUESTED, $payout->status);
        $this->assertSame(300.0, (float) $payout->amount);
        $this->assertSame(15.0, (float) $payout->fee_total);
        $this->assertSame(285.0, (float) $payout->net_amount);
        $this->assertSame('Chave Pix do cadastro', $payout->notes);

        $this->assertSame($payout->id, $first->fresh()->payout_id);
        $this->assertSame($payout->id, $second->fresh()->payout_id);
    }

    public function test_requesting_without_balance_shows_an_error(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(PayoutIndex::class)
            ->call('requestPayout')
            ->assertDispatched('toast', fn ($name, $params) => $params[0]['type'] === 'error'
                && str_contains($params[0]['message'], 'Não há saldo disponível'));

        $this->assertDatabaseCount('payouts', 0);
    }

    public function test_admin_marks_a_payout_as_paid(): void
    {
        $teacher = Teacher::factory()->create();
        $this->paidOnlinePayment($teacher, 400);
        $payout = app(PayoutService::class)->request($teacher);

        $admin = User::factory(['role' => 'admin'])->create();
        $this->actingAs($admin);

        Livewire::test(AdminPayoutIndex::class)
            ->assertSee($teacher->name)
            ->call('markPaid', $payout->id)
            ->assertDispatched('toast', fn ($name, $params) => $params[0]['type'] === 'success');

        $payout->refresh();
        $this->assertSame(PayoutStatus::PAID, $payout->status);
        $this->assertNotNull($payout->paid_at);
    }

    public function test_admin_cancels_a_payout_and_the_charges_return_to_the_balance(): void
    {
        $teacher = Teacher::factory()->create();
        $payment = $this->paidOnlinePayment($teacher, 200);
        $payout = app(PayoutService::class)->request($teacher);

        $admin = User::factory(['role' => 'admin'])->create();
        $this->actingAs($admin);

        Livewire::test(AdminPayoutIndex::class)
            ->call('cancel', $payout->id);

        $this->assertSame(PayoutStatus::CANCELLED, $payout->fresh()->status);
        $this->assertNull($payment->fresh()->payout_id);
    }

    public function test_teacher_cannot_open_the_admin_payouts_page(): void
    {
        $this->seed(ConfigTableSeeder::class);

        $teacher = Teacher::factory()->create();
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($teacher->user);
        $this->get('/admin/plataforma/repasses')->assertForbidden();

        // O admin não tem tenant: a página do treinador é exclusiva dele.
        $this->actingAs($admin);
        $this->get('/admin/repasses')->assertForbidden();
    }

    public function test_payment_form_creates_an_online_charge(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        $this->actingAs($teacher->user);

        Livewire::test(PaymentForm::class)
            ->set('student_id', $student->id)
            ->set('description', 'Mensalidade')
            ->set('amount', '200')
            ->set('due_date', now()->addDays(5)->toDateString())
            ->set('channel', PaymentChannel::ONLINE->value)
            ->call('save')
            ->assertHasNoErrors();

        $payment = Payment::withoutGlobalScopes()->firstOrFail();
        $this->assertSame(PaymentChannel::ONLINE, $payment->channel);
        $this->assertTrue($payment->isPayable());
    }

    public function test_payment_form_shows_the_manual_quota_error(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        Payment::factory()->count(10)->forStudent($student)->create([
            'channel' => PaymentChannel::MANUAL,
        ]);

        $this->actingAs($teacher->user);

        Livewire::test(PaymentForm::class)
            ->set('student_id', $student->id)
            ->set('description', 'Mensalidade')
            ->set('amount', '100')
            ->set('due_date', now()->addDay()->toDateString())
            ->call('save')
            ->assertHasErrors('channel');

        $this->assertDatabaseCount('payments', 10);
    }

    public function test_payments_index_shows_the_channel_and_the_kpis(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        $online = $this->paidOnlinePayment($teacher, 200);
        $online->forceFill(['student_id' => $student->id, 'teacher_id' => $teacher->id])->save();

        Payment::factory()->count(2)->forStudent($student)->create([
            'channel' => PaymentChannel::MANUAL,
        ]);

        $this->actingAs($teacher->user);

        $this->get('/admin/pagamentos')
            ->assertOk()
            ->assertSee('Cobranças manuais este mês')
            ->assertSee('2 / 10', false)
            ->assertSee('Aguardando repasse (online)')
            ->assertSee('R$ 190,00');
    }
}
