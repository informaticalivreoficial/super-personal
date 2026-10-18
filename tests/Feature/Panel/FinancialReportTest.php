<?php

namespace Tests\Feature\Panel;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Livewire\Dashboard\Reports\FinancialReport;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\FinancialReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Relatório financeiro do treinador (competência = vencimento):
 * KPIs, quebras por mês/aluno, filtros e exportação em CSV.
 */
class FinancialReportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{teacher: Teacher, studentA: Student, studentB: Student}
     */
    private function arrange(): array
    {
        $teacher = Teacher::factory()->create();
        $studentA = Student::factory()->forTeacher($teacher)->create();
        $studentB = Student::factory()->forTeacher($teacher)->create();

        return compact('teacher', 'studentA', 'studentB');
    }

    private function filters(array $overrides = []): array
    {
        return array_merge([
            'from' => now()->subDays(10)->toDateString(),
            'to' => now()->addDays(10)->toDateString(),
            'student_id' => null,
            'channel' => null,
            'status' => null,
        ], $overrides);
    }

    public function test_page_lists_only_own_payments_and_shows_controls(): void
    {
        ['teacher' => $teacher, 'studentA' => $studentA] = $this->arrange();

        $foreignStudent = Student::factory()->forTeacher(Teacher::factory()->create())->create();

        Payment::factory()->forStudent($studentA)->paid()->create([
            'description' => 'Mensalidade da aluna A',
            'amount' => 200,
        ]);
        Payment::factory()->forStudent($foreignStudent)->paid()->create([
            'description' => 'Cobrança de outro treinador',
            'amount' => 999,
        ]);

        $this->actingAs($teacher->user);

        Livewire::test(FinancialReport::class)
            ->set('from', $this->filters()['from'])
            ->set('to', $this->filters()['to'])
            ->assertSee('Relatório Financeiro')
            ->assertSee('Mensalidade da aluna A')
            ->assertSee('R$ 200,00')
            ->assertSee('Exportar CSV')
            ->assertDontSee('Cobrança de outro treinador')
            ->assertDontSee('R$ 999,00');
    }

    public function test_summary_splits_received_open_and_overdue(): void
    {
        ['teacher' => $teacher, 'studentA' => $studentA] = $this->arrange();

        $due = now()->toDateString();

        // Paga manual: 200 (sem taxa).
        Payment::factory()->forStudent($studentA)->create([
            'amount' => 200,
            'status' => PaymentStatus::PAID,
            'due_date' => $due,
            'paid_at' => now(),
        ]);

        // Paga online: 100 com 5 de taxa e 95 de líquido.
        $online = Payment::factory()->forStudent($studentA)->online()->create([
            'amount' => 100,
            'status' => PaymentStatus::PAID,
            'due_date' => $due,
            'paid_at' => now(),
        ]);
        $online->forceFill(['fee_amount' => 5, 'net_amount' => 95])->save();

        // Pendente de 300 e atrasada de 50.
        Payment::factory()->forStudent($studentA)->create([
            'amount' => 300,
            'status' => PaymentStatus::PENDING,
            'due_date' => now()->addDays(5)->toDateString(),
        ]);
        Payment::factory()->forStudent($studentA)->create([
            'amount' => 50,
            'status' => PaymentStatus::OVERDUE,
            'due_date' => now()->subDays(3)->toDateString(),
        ]);

        // Cancelada não entra em nenhum total.
        Payment::factory()->forStudent($studentA)->create([
            'amount' => 777,
            'status' => PaymentStatus::CANCELLED,
            'due_date' => $due,
        ]);

        $this->actingAs($teacher->user);

        $summary = app(FinancialReportService::class)->summary($this->filters([
            'from' => now()->subDays(10)->toDateString(),
            'to' => now()->addDays(10)->toDateString(),
        ]));

        $this->assertSame(4, $summary['count']);
        $this->assertSame(3, $summary['count_manual']);
        $this->assertSame(1, $summary['count_online']);
        $this->assertSame(2, $summary['count_paid']);
        $this->assertSame(1, $summary['count_open']);
        $this->assertSame(1, $summary['count_overdue']);
        $this->assertSame(300.0, $summary['received']);
        $this->assertSame(5.0, $summary['fees']);
        $this->assertSame(295.0, $summary['net']);
        $this->assertSame(300.0, $summary['open_amount']);
        $this->assertSame(50.0, $summary['overdue_amount']);

        // A tela mostra os mesmos números.
        Livewire::test(FinancialReport::class)
            ->set('from', $this->filters()['from'])
            ->set('to', $this->filters()['to'])
            ->assertSee('R$ 300,00')
            ->assertSee('R$ 50,00')
            ->assertSee('R$ 295,00');
    }

    public function test_pending_with_past_due_date_counts_as_overdue(): void
    {
        ['teacher' => $teacher, 'studentA' => $studentA] = $this->arrange();

        Payment::factory()->forStudent($studentA)->create([
            'amount' => 120,
            'status' => PaymentStatus::PENDING,
            'due_date' => now()->subDays(2)->toDateString(),
        ]);

        $this->actingAs($teacher->user);

        $summary = app(FinancialReportService::class)->summary($this->filters());

        $this->assertSame(1, $summary['count_overdue']);
        $this->assertSame(0, $summary['count_open']);
        $this->assertSame(120.0, $summary['overdue_amount']);
        $this->assertSame(0.0, $summary['open_amount']);
    }

    public function test_breaks_down_by_month_and_by_student(): void
    {
        ['teacher' => $teacher, 'studentA' => $studentA, 'studentB' => $studentB] = $this->arrange();

        $previousMonth = now()->subMonthNoOverflow()->startOfMonth()->addDays(9);

        // Mês anterior: 100 pagos (manual).
        Payment::factory()->forStudent($studentA)->create([
            'description' => 'Mensalidade do mês passado',
            'amount' => 100,
            'status' => PaymentStatus::PAID,
            'due_date' => $previousMonth->toDateString(),
            'paid_at' => $previousMonth->copy()->addDay(),
        ]);

        // Mês atual: 200 do aluno A (online, líquido 190) e 300 do aluno B.
        $online = Payment::factory()->forStudent($studentA)->online()->create([
            'amount' => 200,
            'status' => PaymentStatus::PAID,
            'due_date' => now()->startOfMonth()->addDay()->toDateString(),
            'paid_at' => now(),
        ]);
        $online->forceFill(['fee_amount' => 10, 'net_amount' => 190])->save();

        Payment::factory()->forStudent($studentB)->create([
            'amount' => 300,
            'status' => PaymentStatus::PAID,
            'due_date' => now()->startOfMonth()->addDays(2)->toDateString(),
            'paid_at' => now(),
        ]);

        $this->actingAs($teacher->user);

        $service = app(FinancialReportService::class);
        $filters = $service->filters([
            'from' => $previousMonth->toDateString(),
            'to' => now()->endOfMonth()->toDateString(),
        ]);

        $months = $service->byMonth($filters);
        $this->assertCount(2, $months);
        $this->assertSame($previousMonth->format('Y-m'), $months[0]['month']);
        $this->assertSame(100.0, $months[0]['received']);
        $this->assertSame(100.0, $months[0]['net']);
        $this->assertSame(now()->format('Y-m'), $months[1]['month']);
        $this->assertSame(500.0, $months[1]['received']);
        $this->assertSame(10.0, $months[1]['fees']);
        $this->assertSame(490.0, $months[1]['net']);
        $this->assertSame(2, $months[1]['count']);

        $students = $service->byStudent($filters);
        $this->assertCount(2, $students);
        $this->assertSame($studentB->name, $students[0]['name']);
        $this->assertSame(300.0, $students[0]['net']);
        $this->assertSame($studentA->name, $students[1]['name']);
        $this->assertSame(290.0, $students[1]['net']);
        $this->assertSame(2, $students[1]['count']);

        // A tela renderiza as duas quebras.
        Livewire::test(FinancialReport::class)
            ->set('from', $filters['from'])
            ->set('to', $filters['to'])
            ->assertSee('Recebido por mês')
            ->assertSee('Recebido por aluno')
            ->assertSee($studentB->name);
    }

    public function test_filters_restrict_the_list(): void
    {
        ['teacher' => $teacher, 'studentA' => $studentA, 'studentB' => $studentB] = $this->arrange();

        $due = now()->toDateString();

        Payment::factory()->forStudent($studentA)->create([
            'description' => 'Mensalidade manual da A',
            'amount' => 100,
            'status' => PaymentStatus::PENDING,
            'due_date' => $due,
        ]);
        Payment::factory()->forStudent($studentA)->online()->create([
            'description' => 'Cobrança online da A',
            'amount' => 200,
            'status' => PaymentStatus::PAID,
            'due_date' => $due,
            'paid_at' => now(),
        ]);
        Payment::factory()->forStudent($studentB)->create([
            'description' => 'Mensalidade manual da B',
            'amount' => 300,
            'status' => PaymentStatus::PENDING,
            'due_date' => $due,
        ]);

        $this->actingAs($teacher->user);

        Livewire::test(FinancialReport::class)
            ->set('from', now()->subDay()->toDateString())
            ->set('to', now()->addDay()->toDateString())
            ->set('filterChannel', PaymentChannel::ONLINE->value)
            ->assertSee('Cobrança online da A')
            ->assertDontSee('Mensalidade manual da A')
            ->assertDontSee('Mensalidade manual da B')
            ->set('filterChannel', '')
            ->set('filterStatus', PaymentStatus::PENDING->value)
            ->assertSee('Mensalidade manual da A')
            ->assertSee('Mensalidade manual da B')
            ->assertDontSee('Cobrança online da A')
            ->set('filterStatus', '')
            ->set('filterStudentId', $studentB->id)
            ->assertSee('Mensalidade manual da B')
            ->assertDontSee('Mensalidade manual da A');
    }

    public function test_period_presets_and_clear_filters(): void
    {
        ['teacher' => $teacher, 'studentA' => $studentA] = $this->arrange();

        $this->actingAs($teacher->user);

        $component = Livewire::test(FinancialReport::class)
            ->assertSet('period', 'month')
            ->assertSet('from', now()->startOfMonth()->toDateString())
            ->assertSet('to', now()->endOfMonth()->toDateString())
            ->call('setPeriod', 'last_month')
            ->assertSet('from', now()->subMonthNoOverflow()->startOfMonth()->toDateString())
            ->assertSet('to', now()->subMonthNoOverflow()->endOfMonth()->toDateString())
            ->call('setPeriod', 'year')
            ->assertSet('from', now()->startOfYear()->toDateString())
            ->assertSet('to', now()->endOfYear()->toDateString());

        // Datas digitadas viram período personalizado.
        $component
            ->set('from', now()->subDays(3)->toDateString())
            ->assertSet('period', 'custom')
            ->set('filterChannel', PaymentChannel::ONLINE->value)
            ->set('filterStatus', PaymentStatus::PAID->value)
            ->set('filterStudentId', $studentA->id)
            ->call('clearFilters')
            ->assertSet('period', 'month')
            ->assertSet('from', now()->startOfMonth()->toDateString())
            ->assertSet('filterChannel', '')
            ->assertSet('filterStatus', '')
            ->assertSet('filterStudentId', null);
    }

    public function test_service_swaps_inverted_dates_and_ignores_invalid_filters(): void
    {
        $service = app(FinancialReportService::class);

        $filters = $service->filters([
            'from' => now()->addDays(5)->toDateString(),
            'to' => now()->toDateString(),
            'channel' => 'inexistente',
            'status' => 'inexistente',
            'student_id' => 0,
        ]);

        $this->assertSame(now()->toDateString(), $filters['from']);
        $this->assertSame(now()->addDays(5)->toDateString(), $filters['to']);
        $this->assertNull($filters['channel']);
        $this->assertNull($filters['status']);
        $this->assertNull($filters['student_id']);

        // Sem datas, o padrão é o mês corrente.
        $default = $service->filters([]);
        $this->assertSame(now()->startOfMonth()->toDateString(), $default['from']);
        $this->assertSame(now()->endOfMonth()->toDateString(), $default['to']);
    }

    public function test_list_is_paginated(): void
    {
        ['teacher' => $teacher, 'studentA' => $studentA] = $this->arrange();

        for ($i = 1; $i <= 16; $i++) {
            Payment::factory()->forStudent($studentA)->create([
                'description' => 'Cobrança do período',
                'amount' => 100 + $i,
                'due_date' => now()->startOfMonth()->addDay()->toDateString(),
            ]);
        }

        $this->actingAs($teacher->user);

        $component = Livewire::test(FinancialReport::class)
            ->assertSee('16 registro(s)');

        // 15 por página.
        $this->assertSame(15, $component->viewData('payments')->count());
        $this->assertSame(16, $component->viewData('payments')->total());
    }

    public function test_exports_csv_with_the_current_filters(): void
    {
        ['teacher' => $teacher, 'studentA' => $studentA] = $this->arrange();

        $foreignStudent = Student::factory()->forTeacher(Teacher::factory()->create())->create();

        Payment::factory()->forStudent($studentA)->create([
            'description' => 'Mensalidade exportada',
            'amount' => 250.5,
            'status' => PaymentStatus::PAID,
            'due_date' => now()->toDateString(),
            'paid_at' => now(),
        ]);
        Payment::factory()->forStudent($foreignStudent)->create([
            'description' => 'Cobrança de outro treinador',
            'amount' => 999,
            'due_date' => now()->toDateString(),
        ]);

        $this->actingAs($teacher->user);

        $response = $this->get(route('reports.financial.export', [
            'from' => now()->subDay()->toDateString(),
            'to' => now()->addDay()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertDownload('relatorio-financeiro-'.now()->subDay()->toDateString().'-a-'.now()->addDay()->toDateString().'.csv');

        $content = $response->streamedContent();

        $this->assertStringContainsString('Vencimento;Aluno;Descrição;Canal;Situação', $content);
        $this->assertStringContainsString($studentA->name, $content);
        $this->assertStringContainsString('Mensalidade exportada', $content);
        $this->assertStringContainsString('250,50', $content);
        $this->assertStringContainsString('Pago', $content);
        $this->assertStringNotContainsString('Cobrança de outro treinador', $content);
        $this->assertStringNotContainsString('999', $content);
    }

    public function test_export_respects_the_status_filter(): void
    {
        ['teacher' => $teacher, 'studentA' => $studentA] = $this->arrange();

        $due = now()->toDateString();

        Payment::factory()->forStudent($studentA)->create([
            'description' => 'Paga no filtro',
            'amount' => 100,
            'status' => PaymentStatus::PAID,
            'due_date' => $due,
            'paid_at' => now(),
        ]);
        Payment::factory()->forStudent($studentA)->create([
            'description' => 'Pendente fora do filtro',
            'amount' => 200,
            'status' => PaymentStatus::PENDING,
            'due_date' => $due,
        ]);

        $this->actingAs($teacher->user);

        $content = $this->get(route('reports.financial.export', [
            'from' => $due,
            'to' => $due,
            'status' => PaymentStatus::PAID->value,
        ]))->streamedContent();

        $this->assertStringContainsString('Paga no filtro', $content);
        $this->assertStringNotContainsString('Pendente fora do filtro', $content);
    }

    public function test_access_is_restricted_to_teachers(): void
    {
        $teacher = Teacher::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'status' => 1]);

        // Convidado → login.
        $this->get(route('reports.financial'))->assertRedirect(route('login'));
        $this->get(route('reports.financial.export'))->assertRedirect(route('login'));

        // Treinador → 200 (página e CSV).
        $this->actingAs($teacher->user);
        $this->get(route('reports.financial'))->assertOk();
        $this->get(route('reports.financial.export'))->assertOk();

        // Admin da plataforma não tem tenant → 403 nas duas.
        $this->actingAs($admin);
        $this->get(route('reports.financial'))->assertForbidden();
        $this->get(route('reports.financial.export'))->assertForbidden();
    }

    public function test_date_inputs_are_reported_in_the_header(): void
    {
        ['teacher' => $teacher] = $this->arrange();

        $this->actingAs($teacher->user);

        Livewire::test(FinancialReport::class)
            ->set('from', '2026-03-01')
            ->set('to', '2026-03-31')
            ->assertSee('01/03/2026')
            ->assertSee('31/03/2026')
            ->assertSee('Período personalizado');
    }

    /**
     * Garante que a página aceita datas válidas vindas de fora sem quebrar.
     */
    public function test_invalid_dates_fall_back_to_the_current_month(): void
    {
        ['teacher' => $teacher] = $this->arrange();

        $this->actingAs($teacher->user);

        Livewire::test(FinancialReport::class)
            ->set('from', 'data-invalida')
            ->set('to', '')
            ->assertOk()
            ->assertSee(Carbon::now()->startOfMonth()->format('d/m/Y'));
    }
}
