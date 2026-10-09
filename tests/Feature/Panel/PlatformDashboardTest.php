<?php

namespace Tests\Feature\Panel;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\Teacher;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_platform_metrics(): void
    {
        $active = Teacher::factory()->create(['name' => 'Professora Ana']);
        Teacher::factory()->inactive()->create();
        Subscription::factory()->forTeacher($active)->create([
            'status' => SubscriptionStatus::ACTIVE,
            'amount' => 199.90,
        ]);
        Student::factory()->forTeacher($active)->create();

        $admin = User::factory(['role' => 'admin'])->create();
        $this->actingAs($admin);

        $this->get(route('admin'))
            ->assertOk()
            ->assertSee('Visão geral da plataforma')
            ->assertSee('Professores (tenants)')
            ->assertSee('Assinaturas ativas')
            ->assertSee('R$ 199,90')
            ->assertSee('Novo professor')
            ->assertDontSee('Visão geral do seu trabalho');
    }

    public function test_platform_dashboard_service_aggregates_across_tenants(): void
    {
        $teacherA = Teacher::factory()->create();
        Teacher::factory()->inactive()->create();
        $teacherC = Teacher::factory()->create();

        Subscription::factory()->forTeacher($teacherA)->create([
            'status' => SubscriptionStatus::ACTIVE,
            'amount' => 100,
        ]);
        Subscription::factory()->forTeacher($teacherC)->create([
            'status' => SubscriptionStatus::PAST_DUE,
            'amount' => 50,
        ]);

        $studentA = Student::factory()->forTeacher($teacherA)->create();
        Student::factory()->forTeacher($teacherC)->create();

        // Pagamento pendente com vencimento no passado = em atraso (plataforma).
        Payment::factory()->forStudent($studentA)->create([
            'due_date' => now()->subDay()->toDateString(),
            'status' => PaymentStatus::PENDING,
        ]);

        $admin = User::factory(['role' => 'admin'])->create();
        $this->actingAs($admin);

        $stats = app(DashboardService::class)->platformDashboard();

        $this->assertSame(3, $stats['teachers']['total']);
        $this->assertSame(2, $stats['teachers']['active']);
        $this->assertSame(2, $stats['students']['total']);
        $this->assertSame(1, $stats['subscriptions']['active']);
        $this->assertSame(1, $stats['subscriptions']['past_due']);
        $this->assertSame(1, $stats['subscriptions']['without']);
        $this->assertSame(100.0, $stats['subscriptions']['monthly_amount']);
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
            ->assertDontSee('Receita mensal');
    }
}
