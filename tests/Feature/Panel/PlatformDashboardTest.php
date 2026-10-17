<?php

namespace Tests\Feature\Panel;

use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Painel do admin: visão da plataforma com a receita do novo modelo —
 * comissão retida nas cobranças online (não existe mais assinatura).
 */
class PlatformDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_platform_metrics(): void
    {
        $teacher = Teacher::factory()->create(['name' => 'Treinadora Ana']);
        Teacher::factory()->inactive()->create();

        $student = Student::factory()->forTeacher($teacher)->create();

        // Cobrança online paga no mês: comissão de R$ 10,00 (150 - 140 líquido).
        Payment::factory()->forStudent($student)->online()->paid()->create([
            'amount' => 150,
            'fee_amount' => 10,
            'net_amount' => 140,
            'paid_at' => now()->subDay(),
        ]);

        Payout::factory()->forTeacher($teacher)->create([
            'net_amount' => 140,
            'status' => PayoutStatus::REQUESTED,
        ]);

        $admin = User::factory(['role' => 'admin'])->create();
        $this->actingAs($admin);

        $this->get(route('admin'))
            ->assertOk()
            ->assertSee('Visão geral da plataforma')
            ->assertSee('Treinadores (tenants)')
            ->assertSee('Comissões no mês')
            ->assertSee('R$ 10,00')
            ->assertSee('Repasses a pagar')
            ->assertSee('R$ 140,00')
            ->assertSee('Novo treinador')
            ->assertDontSee('Visão geral do seu trabalho');
    }

    public function test_platform_dashboard_service_aggregates_across_tenants(): void
    {
        $teacherA = Teacher::factory()->create();
        Teacher::factory()->inactive()->create();
        $teacherC = Teacher::factory()->create();

        $studentA = Student::factory()->forTeacher($teacherA)->create();
        $studentC = Student::factory()->forTeacher($teacherC)->create();

        // Comissão do mês (tenant A) — entra no mês e no total.
        Payment::factory()->forStudent($studentA)->online()->paid()->create([
            'amount' => 100,
            'fee_amount' => 5,
            'net_amount' => 95,
            'paid_at' => now()->subDay(),
        ]);

        // Comissão de outro tenant no mês — soma também (visão global).
        Payment::factory()->forStudent($studentC)->online()->paid()->create([
            'amount' => 200,
            'fee_amount' => 10,
            'net_amount' => 190,
            'paid_at' => now()->subDays(2),
        ]);

        // Cobrança manual paga NÃO gera comissão (sem taxa).
        Payment::factory()->forStudent($studentA)->paid()->create([
            'amount' => 300,
            'fee_amount' => null,
            'net_amount' => null,
            'paid_at' => now()->subDay(),
        ]);

        // Pagamento pendente com vencimento no passado = em atraso (plataforma).
        Payment::factory()->forStudent($studentA)->create([
            'due_date' => now()->subDay()->toDateString(),
            'status' => PaymentStatus::PENDING,
        ]);

        // Repasses: um solicitado (a pagar) e um já pago.
        Payout::factory()->forTeacher($teacherA)->create([
            'amount' => 100,
            'fee_total' => 5,
            'net_amount' => 95,
            'status' => PayoutStatus::REQUESTED,
        ]);
        Payout::factory()->forTeacher($teacherC)->paid()->create([
            'amount' => 200,
            'fee_total' => 10,
            'net_amount' => 190,
        ]);

        $admin = User::factory(['role' => 'admin'])->create();
        $this->actingAs($admin);

        $stats = app(DashboardService::class)->platformDashboard();

        $this->assertSame(3, $stats['teachers']['total']);
        $this->assertSame(2, $stats['teachers']['active']);
        $this->assertSame(2, $stats['students']['total']);
        $this->assertSame(15.0, $stats['commissions']['month_amount']);
        $this->assertSame(15.0, $stats['commissions']['total_amount']);
        $this->assertSame(2, $stats['commissions']['paid_count']);
        $this->assertSame(95.0, $stats['payouts']['requested_amount']);
        $this->assertSame(1, $stats['payouts']['requested_count']);
        $this->assertSame(190.0, $stats['payouts']['paid_amount']);
        $this->assertSame(1, $stats['payments']['overdue_count']);
    }

    public function test_teacher_sees_own_dashboard_not_platform_view(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        $this->get(route('admin'))
            ->assertOk()
            ->assertSee('Visão geral do seu trabalho')
            ->assertDontSee('Visão geral da plataforma')
            ->assertDontSee('Comissões no mês');
    }
}
