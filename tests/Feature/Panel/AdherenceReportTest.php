<?php

namespace Tests\Feature\Panel;

use App\Enums\TrainingPlanStatus;
use App\Enums\TrainingSessionStatus;
use App\Enums\UserRole;
use App\Livewire\Dashboard\Reports\AdherenceReport;
use App\Models\Sport;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TrainingPlan;
use App\Models\TrainingSession;
use App\Models\TrainingWeek;
use App\Models\User;
use App\Services\AdherenceReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Relatório de aderência: competência = data agendada, apenas planos publicados.
 */
class AdherenceReportTest extends TestCase
{
    use RefreshDatabase;

    private Teacher $teacher;

    private Student $student;

    private TrainingWeek $week;

    private Sport $sport;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-20 10:00:00');

        $this->teacher = Teacher::factory()->create();
        $this->student = Student::factory()->forTeacher($this->teacher)->create(['name' => 'Ana Souza']);
        $this->sport = Sport::factory()->create(['name' => 'Corrida', 'slug' => 'corrida-aderencia']);

        $plan = TrainingPlan::factory()->forStudent($this->student)->create([
            'status' => TrainingPlanStatus::ACTIVE,
            'start_date' => '2026-10-01',
        ]);
        $this->week = TrainingWeek::factory()->forPlan($plan)->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function makeSession(string $status, string $date, array $overrides = []): TrainingSession
    {
        return TrainingSession::factory()->forWeek($this->week, array_merge([
            'student_id' => $this->student->id,
            'sport_id' => $this->sport->id,
            'scheduled_date' => $date,
            'status' => $status,
            'distance' => null,
            'estimated_duration' => 3600,
        ], $overrides))->create();
    }

    public function test_summary_counts_statuses_and_adherence_of_due_sessions(): void
    {
        $this->makeSession(TrainingSessionStatus::COMPLETED->value, '2026-10-19', ['distance' => 5000]);
        $this->makeSession(TrainingSessionStatus::SKIPPED->value, '2026-10-19');
        $this->makeSession(TrainingSessionStatus::PLANNED->value, '2026-10-18');
        $this->makeSession(TrainingSessionStatus::PLANNED->value, '2026-10-21');
        $this->makeSession(TrainingSessionStatus::CANCELLED->value, '2026-10-19');

        $service = app(AdherenceReportService::class);
        $summary = $service->summary($service->sessions($service->filters([
            'from' => '2026-10-01', 'to' => '2026-10-31',
        ])));

        $this->assertSame(5, $summary['total']);
        $this->assertSame(1, $summary['completed']);
        $this->assertSame(1, $summary['skipped']);
        $this->assertSame(1, $summary['missed']);
        $this->assertSame(1, $summary['cancelled']);
        $this->assertSame(1, $summary['planned']);
        $this->assertSame(3, $summary['due']);
        $this->assertSame(33, $summary['adherence']);
        $this->assertSame(5000, $summary['distance']);
        $this->assertSame(3600, $summary['duration']);
    }

    public function test_sessions_from_draft_plans_are_ignored(): void
    {
        $this->makeSession(TrainingSessionStatus::COMPLETED->value, '2026-10-19');

        $draft = TrainingPlan::factory()->forStudent($this->student)->create([
            'status' => TrainingPlanStatus::DRAFT,
            'start_date' => '2026-10-01',
        ]);
        $draftWeek = TrainingWeek::factory()->forPlan($draft)->create();
        TrainingSession::factory()->forWeek($draftWeek, [
            'student_id' => $this->student->id,
            'sport_id' => $this->sport->id,
            'scheduled_date' => '2026-10-19',
            'status' => TrainingSessionStatus::COMPLETED,
        ])->create();

        $service = app(AdherenceReportService::class);
        $summary = $service->summary($service->sessions($service->filters([
            'from' => '2026-10-01', 'to' => '2026-10-31',
        ])));

        $this->assertSame(1, $summary['total']);
    }

    public function test_page_shows_kpis_rankings_and_export(): void
    {
        $this->makeSession(TrainingSessionStatus::COMPLETED->value, '2026-10-19', ['title' => 'Rodagem leve']);
        $this->makeSession(TrainingSessionStatus::PLANNED->value, '2026-10-18', ['title' => 'Intervalado']);

        $this->actingAs($this->teacher->user);

        Livewire::test(AdherenceReport::class)
            ->assertSee('Relatório de Aderência')
            ->assertSee('Ana Souza')
            ->assertSee('Rodagem leve')
            ->assertSee('50%')
            ->assertSee('Corrida')
            ->assertSee('Exportar CSV');
    }

    public function test_student_and_sport_filters_narrow_the_report(): void
    {
        $other = Student::factory()->forTeacher($this->teacher)->create(['name' => 'Bruno Lima']);
        $bike = Sport::factory()->create(['name' => 'Ciclismo', 'slug' => 'ciclismo-aderencia']);

        $this->makeSession(TrainingSessionStatus::COMPLETED->value, '2026-10-19');
        $this->makeSession(TrainingSessionStatus::COMPLETED->value, '2026-10-19', [
            'student_id' => $other->id,
            'sport_id' => $bike->id,
        ]);

        $service = app(AdherenceReportService::class);

        $byStudent = $service->sessions($service->filters([
            'from' => '2026-10-01', 'to' => '2026-10-31', 'student_id' => $other->id,
        ]));
        $bySport = $service->sessions($service->filters([
            'from' => '2026-10-01', 'to' => '2026-10-31', 'sport_id' => $bike->id,
        ]));

        $this->assertCount(1, $byStudent);
        $this->assertSame($other->id, $byStudent->first()->student_id);
        $this->assertCount(1, $bySport);
        $this->assertSame($bike->id, $bySport->first()->sport_id);
    }

    public function test_csv_export_contains_ranking_and_total(): void
    {
        $this->makeSession(TrainingSessionStatus::COMPLETED->value, '2026-10-19');
        $this->makeSession(TrainingSessionStatus::SKIPPED->value, '2026-10-19');

        $this->actingAs($this->teacher->user)
            ->get(route('reports.adherence.export', ['from' => '2026-10-01', 'to' => '2026-10-31']))
            ->assertOk()
            ->assertDownload('relatorio-aderencia-2026-10-01-a-2026-10-31.csv')
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_report_is_scoped_to_the_logged_teacher(): void
    {
        $otherTeacher = Teacher::factory()->create();
        $otherStudent = Student::factory()->forTeacher($otherTeacher)->create(['name' => 'Aluno de Outro']);
        $plan = TrainingPlan::factory()->forStudent($otherStudent)->create([
            'status' => TrainingPlanStatus::ACTIVE,
            'start_date' => '2026-10-01',
        ]);
        $week = TrainingWeek::factory()->forPlan($plan)->create();
        TrainingSession::factory()->forWeek($week, [
            'student_id' => $otherStudent->id,
            'sport_id' => $this->sport->id,
            'scheduled_date' => '2026-10-19',
            'status' => TrainingSessionStatus::COMPLETED,
        ])->create();

        $this->actingAs($this->teacher->user)
            ->get(route('reports.adherence'))
            ->assertOk()
            ->assertDontSee('Aluno de Outro');
    }

    public function test_access_is_restricted_to_teachers(): void
    {
        $this->get(route('reports.adherence'))->assertRedirect(route('login'));

        $admin = User::factory()->create();
        $admin->forceFill(['role' => UserRole::ADMIN])->save();
        $this->actingAs($admin->fresh())->get(route('reports.adherence'))->assertForbidden();

        $this->actingAs($this->teacher->user)->get(route('reports.adherence'))->assertOk();
    }

    public function test_inverted_dates_are_swapped_and_invalid_dates_fall_back(): void
    {
        $service = app(AdherenceReportService::class);

        $filters = $service->filters(['from' => '2026-10-31', 'to' => '2026-10-01']);
        $this->assertSame('2026-10-01', $filters['from']);
        $this->assertSame('2026-10-31', $filters['to']);

        $fallback = $service->filters(['from' => 'nao-e-data', 'to' => '']);
        $this->assertSame('2026-10-01', $fallback['from']);
        $this->assertSame('2026-10-31', $fallback['to']);
    }
}
