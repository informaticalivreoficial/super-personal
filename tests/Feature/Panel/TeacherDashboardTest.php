<?php

namespace Tests\Feature\Panel;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Sport;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TrainingPlan;
use App\Models\TrainingSession;
use App\Models\TrainingWeek;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Painel do treinador: dinheiro do mês e as listas acionáveis
 * (cobranças em atraso e treinos de hoje) — não existe assinatura.
 */
class TeacherDashboardTest extends TestCase
{
    use RefreshDatabase;

    /** Modalidade reaproveitada pelos testes (o factory usa nomes únicos fixos). */
    private ?Sport $sport = null;

    private function sport(): Sport
    {
        return $this->sport ??= Sport::factory()->create();
    }

    /** Sessão de treino de hoje para o aluno (dentro de um plano/semana). */
    private function sessionToday(Student $student, array $overrides = []): TrainingSession
    {
        $plan = TrainingPlan::factory()->forStudent($student)->create();
        $week = TrainingWeek::factory()->forPlan($plan)->create();

        return TrainingSession::factory()
            ->forWeek($week, array_merge([
                'sport_id' => $this->sport()->id,
                'scheduled_date' => now()->toDateString(),
            ], $overrides))
            ->create();
    }

    /** Cobrança online paga no mês, com taxa/líquido (saldo de repasse). */
    private function paidOnline(Student $student, float $amount, float $net): Payment
    {
        return Payment::factory()->forStudent($student)->online()->create([
            'amount' => $amount,
            'fee_amount' => round($amount - $net, 2),
            'net_amount' => $net,
            'status' => PaymentStatus::PAID,
            'paid_at' => now(),
        ]);
    }

    public function test_teacher_dashboard_shows_money_and_lists(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create(['name' => 'Ana Corredora']);

        $this->sessionToday($student, ['title' => 'Intervalado 6x800m']);
        Payment::factory()->forStudent($student)->create([
            'description' => 'Mensalidade de setembro',
            'amount' => 100,
            'due_date' => now()->subDay()->toDateString(),
            'status' => PaymentStatus::PENDING,
        ]);
        $this->paidOnline($student, 150, 142.50);
        Payment::factory()->forStudent($student)->count(2)->create(['amount' => 200]);

        $this->actingAs($teacher->user);

        $this->get(route('admin'))
            ->assertOk()
            ->assertSee('Visão geral do seu trabalho')
            ->assertSee('A receber')
            // A receber = 100 (vencida) + 400 (duas manuais em aberto).
            ->assertSee('R$ 500,00')
            ->assertSee('1 em atraso')
            ->assertSee('Recebido no mês')
            ->assertSee('Saldo para repasse')
            ->assertSee('R$ 142,50')
            ->assertSee('Cobranças manuais este mês')
            ->assertSeeText('3 / 10')
            ->assertSee('Cobranças em atraso')
            ->assertSee('Ana Corredora')
            ->assertSee('Mensalidade de setembro')
            ->assertSee('Treinos de hoje')
            ->assertSee('Intervalado 6x800m')
            ->assertSee('Planejado');
    }

    public function test_teacher_dashboard_service_computes_money_values(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        // Vencida (status overdue) + pendente com vencimento no passado.
        Payment::factory()->forStudent($student)->overdue()->create(['amount' => 80]);
        Payment::factory()->forStudent($student)->create([
            'amount' => 100,
            'due_date' => now()->subDays(3)->toDateString(),
        ]);
        // Pendente no futuro: em aberto, mas não em atraso.
        Payment::factory()->forStudent($student)->create([
            'amount' => 50,
            'due_date' => now()->addDays(5)->toDateString(),
        ]);
        // Paga no mês (passado, mês anterior não conta).
        $this->paidOnline($student, 300, 285);
        Payment::factory()->forStudent($student)->paid()->create([
            'amount' => 999,
            'paid_at' => now()->subMonth()->startOfMonth(),
        ]);

        $this->actingAs($teacher->user);
        $stats = app(DashboardService::class)->teacherDashboard();

        $this->assertSame(230.0, $stats['money']['open_amount']);
        $this->assertSame(180.0, $stats['money']['overdue_amount']);
        $this->assertSame(2, $stats['payments']['overdue_count']);
        // Mês anterior fora do "recebido no mês".
        $this->assertSame(300.0, $stats['money']['received_this_month_amount']);
        $this->assertSame(285.0, $stats['money']['awaiting_payout_amount']);
        $this->assertSame(0.0, $stats['money']['paid_out_total']);
        // Cota do mês = cobranças manuais criadas agora (inclusive a paga com
        // `paid_at` no mês passado: a regra olha `created_at`).
        $this->assertSame(4, $stats['money']['manual_this_month']);
        $this->assertSame(PaymentService::MANUAL_MONTHLY_LIMIT, $stats['money']['manual_limit']);
    }

    public function test_teacher_dashboard_service_lists_are_scoped_and_limited(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        // 7 cobranças em atraso: a lista mostra no máximo 5, da mais antiga.
        Payment::factory()->forStudent($student)->count(7)->sequence(
            fn ($sequence) => [
                'amount' => 10,
                'due_date' => now()->subDays(20 - $sequence->index)->toDateString(),
            ],
        )->create();

        // 10 treinos hoje: a lista mostra no máximo 8.
        foreach (range(1, 10) as $index) {
            $this->sessionToday($student, ['title' => "Treino {$index}"]);
        }
        // Amanhã não entra na lista de hoje.
        $this->sessionToday($student, ['title' => 'Treino de amanhã', 'scheduled_date' => now()->addDay()->toDateString()]);

        $this->actingAs($teacher->user);
        $stats = app(DashboardService::class)->teacherDashboard();

        $this->assertCount(5, $stats['overdue_payments']);
        $this->assertSame(now()->subDays(20)->toDateString(), $stats['overdue_payments']->first()->due_date->toDateString());
        $this->assertCount(8, $stats['today_sessions']);
        $this->assertSame(10, $stats['trainings']['today']);
    }

    public function test_teacher_dashboard_does_not_leak_other_tenant_data(): void
    {
        $teacher = Teacher::factory()->create();
        Student::factory()->forTeacher($teacher)->create(['name' => 'Aluno do professor A']);

        $other = Teacher::factory()->create();
        $otherStudent = Student::factory()->forTeacher($other)->create(['name' => 'Aluno do professor B']);
        Payment::factory()->forStudent($otherStudent)->overdue()->create(['amount' => 500]);
        Payment::factory()->forStudent($otherStudent)->count(9)->create();
        $this->paidOnline($otherStudent, 1000, 950);
        $this->sessionToday($otherStudent, ['title' => 'Treino do professor B']);

        $this->actingAs($teacher->user);
        $stats = app(DashboardService::class)->teacherDashboard();

        $this->assertSame(0.0, $stats['money']['open_amount']);
        $this->assertSame(0.0, $stats['money']['overdue_amount']);
        $this->assertSame(0, $stats['money']['manual_this_month']);
        $this->assertSame(0.0, $stats['money']['awaiting_payout_amount']);
        $this->assertCount(0, $stats['overdue_payments']);
        $this->assertCount(0, $stats['today_sessions']);

        $this->get(route('admin'))
            ->assertOk()
            ->assertSee('Nenhuma cobrança em atraso')
            ->assertSee('Nenhum treino agendado para hoje')
            ->assertDontSee('Aluno do professor B')
            ->assertDontSee('Treino do professor B');
    }

    public function test_teacher_dashboard_shows_empty_states_and_manual_quota(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        // 10 cobranças manuais no mês: cota esgotada.
        Payment::factory()->forStudent($student)->count(10)->create();

        $this->actingAs($teacher->user);

        $this->get(route('admin'))
            ->assertOk()
            ->assertSee('Nenhuma cobrança em atraso')
            ->assertSee('Nenhum treino agendado para hoje')
            ->assertSeeText('10 / 10')
            ->assertSee('Cota do mês atingida');
    }

    public function test_admin_dashboard_keeps_platform_view_without_teacher_money_block(): void
    {
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($admin);

        $this->get(route('admin'))
            ->assertOk()
            ->assertSee('Visão geral da plataforma')
            ->assertSee('Comissões no mês')
            ->assertDontSee('Cobranças manuais este mês')
            ->assertDontSee('Saldo para repasse');
    }
}
