<?php

namespace Tests\Feature\Panel;

use App\Enums\PaymentStatus;
use App\Livewire\Dashboard\Payouts\PayoutBalance;
use App\Livewire\Dashboard\Payouts\PayoutIndex;
use App\Livewire\Dashboard\Payouts\PayoutStatement;
use App\Models\Config;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherPayoutAccount;
use App\Services\PayoutService;
use App\Services\PayoutStatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Prazo de liberação do saque (`config.payout_hold_days`) e o extrato
 * financeiro do treinador (entradas, saídas, filtros e paginação).
 */
class PayoutHoldTest extends TestCase
{
    use RefreshDatabase;

    private function config(int $holdDays): Config
    {
        return Config::query()->create([
            'app_name' => 'Teste',
            'fee_percent' => 5,
            'payout_hold_days' => $holdDays,
        ]);
    }

    private function account(Teacher $teacher): TeacherPayoutAccount
    {
        return TeacherPayoutAccount::factory()->forTeacher($teacher)->pix()->default()->create();
    }

    /** Cobrança online paga (fee/líquido gravados direto: não são fillable). */
    private function paidOnline(Teacher $teacher, float $amount, string $paidAt): Payment
    {
        $student = Student::factory()->forTeacher($teacher)->create();
        $fee = round($amount * 0.05, 2);

        $payment = Payment::factory()->online()->forStudent($student)->create([
            'amount' => $amount,
            'status' => PaymentStatus::PAID,
            'paid_at' => $paidAt,
        ]);

        $payment->forceFill([
            'fee_amount' => $fee,
            'net_amount' => round($amount - $fee, 2),
        ])->save();

        return $payment;
    }

    public function test_balance_follows_the_configured_hold_days(): void
    {
        $this->config(7);

        $teacher = Teacher::factory()->create();
        // 8 dias: já liberou (R$ 190). 2 dias: ainda retido (R$ 95).
        $this->paidOnline($teacher, 200, now()->subDays(8)->toDateTimeString());
        $this->paidOnline($teacher, 100, now()->subDays(2)->toDateTimeString());

        $service = app(PayoutService::class);

        $this->assertSame(7, $service->holdDays());
        $this->assertSame(190.0, $service->balance($teacher));
        $this->assertSame(95.0, $service->releasingTotal($teacher));
    }

    public function test_zero_hold_days_releases_immediately(): void
    {
        $this->config(0);

        $teacher = Teacher::factory()->create();
        $this->paidOnline($teacher, 200, now()->toDateTimeString());

        $service = app(PayoutService::class);

        $this->assertSame(0, $service->holdDays());
        $this->assertSame(190.0, $service->balance($teacher));
        $this->assertSame(0.0, $service->releasingTotal($teacher));
    }

    public function test_fallback_hold_is_used_without_config(): void
    {
        // Sem Config gravada (instalação nova) o padrão de produto vale.
        $this->assertSame(Config::DEFAULT_PAYOUT_HOLD_DAYS, Config::payoutHoldDays());
        $this->assertSame(7, app(PayoutService::class)->holdDays());
    }

    public function test_balance_component_shows_available_and_releasing(): void
    {
        $this->config(7);

        $teacher = Teacher::factory()->create();
        $this->account($teacher);
        $this->paidOnline($teacher, 200, now()->subDays(8)->toDateTimeString());
        $this->paidOnline($teacher, 100, now()->subDays(2)->toDateTimeString());

        $this->actingAs($teacher->user);

        Livewire::test(PayoutBalance::class)
            ->assertOk()
            ->assertSee('Saldo disponível')
            ->assertSee('R$ 190,00')
            ->assertSee('A liberar')
            ->assertSee('R$ 95,00')
            ->assertSee('Solicitar saque');
    }

    public function test_request_uses_only_the_released_charges(): void
    {
        $this->config(7);

        $teacher = Teacher::factory()->create();
        $this->account($teacher);
        $released = $this->paidOnline($teacher, 200, now()->subDays(8)->toDateTimeString());
        $held = $this->paidOnline($teacher, 100, now()->subDays(2)->toDateTimeString());

        $payout = app(PayoutService::class)->request($teacher);

        $this->assertSame(190.0, (float) $payout->net_amount);
        $this->assertSame($payout->id, $released->fresh()->payout_id);
        // O valor retido continua fora do saque.
        $this->assertNull($held->fresh()->payout_id);
    }

    public function test_statement_lists_credits_with_the_money_stage(): void
    {
        $this->config(7);

        $teacher = Teacher::factory()->create();
        $this->account($teacher);
        $released = $this->paidOnline($teacher, 200, now()->subDays(8)->toDateTimeString());
        $this->paidOnline($teacher, 100, now()->subDays(2)->toDateTimeString());

        $service = app(PayoutStatementService::class);
        $filters = [
            'from' => now()->subDays(40)->toDateString(),
            'to' => now()->toDateString(),
        ];

        $entries = $service->entries($teacher, $filters);
        $this->assertCount(2, $entries);
        $this->assertSame(['available', 'releasing'], $entries->pluck('status')->sort()->values()->all());

        $this->actingAs($teacher->user);
        Livewire::test(PayoutStatement::class)
            ->set('from', $filters['from'])
            ->set('to', $filters['to'])
            ->assertSee('Cobrança online')
            ->assertSee('Disponível')
            ->assertSee('A liberar');

        // Cobrança já sacada passa a aparecer como "Repassado".
        app(PayoutService::class)->request($teacher);

        $this->assertSame(
            ['paid_out', 'releasing'],
            $service->entries($teacher, $filters)
                ->where('kind', 'credit')
                ->pluck('status')
                ->sort()
                ->values()
                ->all(),
        );

        Livewire::test(PayoutStatement::class)
            ->set('from', $filters['from'])
            ->set('to', $filters['to'])
            ->assertSee('Repassado');
    }

    public function test_statement_shows_withdrawals_and_totals(): void
    {
        $this->config(7);

        $teacher = Teacher::factory()->create();
        $this->account($teacher);
        $this->paidOnline($teacher, 200, now()->subDays(8)->toDateTimeString());

        $payout = app(PayoutService::class)->request($teacher, null, 'Pix do cadastro');
        app(PayoutService::class)->markPaid($payout);

        $service = app(PayoutStatementService::class);
        $filters = [
            'from' => now()->subDays(40)->toDateString(),
            'to' => now()->toDateString(),
        ];

        $entries = $service->entries($teacher, $filters);
        $this->assertCount(2, $entries);

        $debit = $entries->firstWhere('kind', 'debit');
        $this->assertSame('Saque', $debit['title']);
        $this->assertSame('Pago', $debit['status_label']);
        $this->assertSame(190.0, $debit['amount']);

        $this->assertSame(
            ['credits' => 190.0, 'debits' => 190.0, 'net' => 0.0],
            $service->totals($entries),
        );

        $this->actingAs($teacher->user);

        Livewire::test(PayoutStatement::class)
            ->set('from', $filters['from'])
            ->set('to', $filters['to'])
            ->assertSee('Movimentações')
            ->assertSee('Saque')
            ->assertSee('Pago')
            ->assertSee('R$ 0,00');
    }

    public function test_statement_filters_by_type_and_period(): void
    {
        $this->config(7);

        $teacher = Teacher::factory()->create();
        $this->account($teacher);
        $this->paidOnline($teacher, 200, now()->subDays(8)->toDateTimeString());
        $payout = app(PayoutService::class)->request($teacher);

        $service = app(PayoutStatementService::class);
        $wide = ['from' => now()->subDays(40)->toDateString(), 'to' => now()->toDateString()];

        $this->assertCount(1, $service->entries($teacher, $wide + ['type' => 'credit']));
        $this->assertCount(1, $service->entries($teacher, $wide + ['type' => 'debit']));
        $this->assertCount(2, $service->entries($teacher, $wide));

        // Período sem movimentação devolve vazio (o saque nasce hoje).
        $past = [
            'from' => now()->subDays(40)->toDateString(),
            'to' => now()->subDays(20)->toDateString(),
        ];
        $this->assertCount(0, $service->entries($teacher, $past));

        // Cancelado fica fora do extrato.
        app(PayoutService::class)->cancel($payout);
        $this->assertCount(1, $service->entries($teacher, $wide));
    }

    public function test_statement_ignores_manual_unpaid_and_other_tenants(): void
    {
        $this->config(7);

        $teacher = Teacher::factory()->create();
        $other = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        // Manual paga, online pendente e cobrança de outro treinador.
        Payment::factory()->forStudent($student)->create([
            'amount' => 100, 'status' => PaymentStatus::PAID, 'paid_at' => now()->subDays(8),
        ]);
        Payment::factory()->online()->forStudent($student)->create([
            'amount' => 100, 'status' => PaymentStatus::PENDING,
        ]);
        $this->paidOnline($other, 300, now()->subDays(8)->toDateTimeString());

        $service = app(PayoutStatementService::class);
        $entries = $service->entries($teacher, [
            'from' => now()->subDays(40)->toDateString(),
            'to' => now()->toDateString(),
        ]);

        $this->assertCount(0, $entries);
    }

    public function test_statement_filters_normalize_input(): void
    {
        $service = app(PayoutStatementService::class);

        // Datas invertidas são trocadas; tipo inválido é ignorado; data
        // inválida cai no padrão (início do mês).
        $filters = $service->filters([
            'from' => now()->toDateString(),
            'to' => now()->subDays(5)->toDateString(),
            'type' => 'invalido',
        ]);

        $this->assertSame(now()->subDays(5)->toDateString(), $filters['from']);
        $this->assertSame(now()->toDateString(), $filters['to']);
        $this->assertSame('', $filters['type']);

        $filters = $service->filters(['from' => 'não-é-data', 'to' => null]);
        $this->assertSame(now()->startOfMonth()->toDateString(), $filters['from']);
        $this->assertSame(now()->toDateString(), $filters['to']);
    }

    public function test_statement_is_paginated(): void
    {
        $this->config(7);

        $teacher = Teacher::factory()->create();
        $this->account($teacher);

        for ($i = 0; $i < 16; $i++) {
            $this->paidOnline($teacher, 100, now()->subDays(8)->subHours($i)->toDateTimeString());
        }

        $this->actingAs($teacher->user);

        $component = Livewire::test(PayoutStatement::class)
            ->set('from', now()->subDays(40)->toDateString())
            ->set('to', now()->toDateString());

        $this->assertSame(15, $component->viewData('entries')->count());
        $this->assertSame(16, $component->viewData('entries')->total());
    }

    public function test_statement_clear_filters_returns_to_the_current_month(): void
    {
        $this->config(7);

        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(PayoutStatement::class)
            ->set('from', now()->subYear()->toDateString())
            ->set('to', now()->subYear()->toDateString())
            ->set('type', 'credit')
            ->call('clearFilters')
            ->assertSet('from', now()->startOfMonth()->toDateString())
            ->assertSet('to', now()->toDateString())
            ->assertSet('type', '')
            ->assertSee('Nenhuma movimentação no período.');
    }

    public function test_statement_preset_sets_the_period(): void
    {
        $this->config(7);

        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(PayoutStatement::class)
            ->call('applyPreset', 'last_30')
            ->assertSet('from', now()->subDays(29)->toDateString())
            ->assertSet('to', now()->toDateString());
    }

    public function test_payout_index_shows_the_finance_tabs(): void
    {
        $this->config(7);

        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(PayoutIndex::class)
            ->assertOk()
            ->assertSee('Financeiro')
            ->assertSee('Saldo e saque')
            ->assertSee('Extrato')
            ->assertSee('Formas de recebimento')
            // A aba padrão renderiza o saldo.
            ->assertSee('Saldo disponível')
            ->assertSee('Cobranças liberadas para saque')
            ->call('showTab', 'extrato')
            ->assertSet('tab', 'extrato')
            ->assertSee('Movimentações')
            ->assertDontSee('Cobranças liberadas para saque')
            ->call('showTab', 'contas')
            ->assertSet('tab', 'contas')
            ->assertSee('Nenhuma forma de recebimento cadastrada')
            ->assertDontSee('Cobranças liberadas para saque');
    }
}
