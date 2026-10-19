<?php

namespace Tests\Feature\Panel;

use App\Enums\TrainingSessionStatus;
use App\Livewire\Dashboard\Agenda;
use App\Models\Sport;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TrainingPlan;
use App\Models\TrainingSession;
use App\Models\TrainingWeek;
use App\Models\User;
use App\Services\AgendaService;
use Database\Seeders\ConfigTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Agenda semanal do treinador (/admin/agenda): recorte dos treinos do tenant
 * agrupados por dia, com KPIs, navegação entre semanas e filtros.
 */
class AgendaTest extends TestCase
{
    use RefreshDatabase;

    /** Sessão de treino do aluno numa data (dentro de plano/semana próprios). */
    private function trainingSession(Student $student, string $date, array $overrides = []): TrainingSession
    {
        $plan = TrainingPlan::factory()->forStudent($student)->create();
        $week = TrainingWeek::factory()->forPlan($plan)->create();

        return TrainingSession::factory()
            ->forWeek($week, array_merge(['scheduled_date' => $date], $overrides))
            ->create();
    }

    public function test_agenda_lists_the_week_of_the_tenant(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create(['name' => 'Ana Corredora']);
        $sport = Sport::factory()->create(['name' => 'Corrida']);

        $monday = now()->startOfWeek()->toDateString();

        $this->trainingSession($student, $monday, [
            'title' => 'Intervalado 6x800m',
            'sport_id' => $sport->id,
            'estimated_duration' => 3600,
            'distance' => 8000,
        ]);
        $this->trainingSession($student, now()->toDateString(), [
            'title' => 'Rodagem leve',
            'sport_id' => $sport->id,
            'status' => TrainingSessionStatus::COMPLETED,
        ]);

        // Fora da semana: não deve aparecer.
        $this->trainingSession($student, now()->addWeek()->startOfWeek()->toDateString(), ['title' => 'Treino de outra semana']);

        $this->actingAs($teacher->user);

        Livewire::test(Agenda::class)
            ->assertOk()
            ->assertSee('Agenda semanal')
            ->assertSee('Intervalado 6x800m')
            ->assertSee('Rodagem leve')
            ->assertSee('Ana Corredora')
            ->assertDontSee('Treino de outra semana')
            ->assertSee('Treinos na semana')
            ->assertSee('Semana atual')
            ->assertSee('Hoje')
            ->assertSee('Concluídos')
            ->assertSee(now()->translatedFormat('F'));
    }

    public function test_agenda_navigates_between_weeks(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        $this->trainingSession($student, now()->startOfWeek()->addDay()->toDateString(), ['title' => 'Treino da semana atual']);
        $this->trainingSession($student, now()->addWeek()->startOfWeek()->addDay()->toDateString(), ['title' => 'Treino da próxima semana']);
        $this->trainingSession($student, now()->subWeek()->startOfWeek()->addDay()->toDateString(), ['title' => 'Treino da semana passada']);

        $this->actingAs($teacher->user);

        Livewire::test(Agenda::class)
            ->assertSee('Treino da semana atual')
            ->assertSee('Semana atual')
            ->assertDontSee('Treino da próxima semana')
            ->call('nextWeek')
            ->assertSee('Treino da próxima semana')
            ->assertDontSee('Treino da semana atual')
            ->assertDontSee('Semana atual');
    }

    public function test_agenda_navigation_back_to_the_current_week(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        $this->trainingSession($student, now()->startOfWeek()->addDay()->toDateString(), ['title' => 'Treino da semana atual']);
        $this->trainingSession($student, now()->subWeek()->startOfWeek()->addDay()->toDateString(), ['title' => 'Treino da semana passada']);

        $this->actingAs($teacher->user);

        Livewire::test(Agenda::class)
            ->call('previousWeek')
            ->assertSee('Treino da semana passada')
            ->assertDontSee('Treino da semana atual')
            ->call('currentWeek')
            ->assertSee('Treino da semana atual')
            ->assertDontSee('Treino da semana passada')
            ->assertSee('Semana atual');
    }

    public function test_agenda_filters_by_student_sport_and_status(): void
    {
        $teacher = Teacher::factory()->create();
        $ana = Student::factory()->forTeacher($teacher)->create(['name' => 'Ana Corredora']);
        $bruno = Student::factory()->forTeacher($teacher)->create(['name' => 'Bruno Ciclista']);

        $corrida = Sport::factory()->create(['name' => 'Corrida']);
        $ciclismo = Sport::factory()->create(['name' => 'Ciclismo']);

        $date = now()->startOfWeek()->addDay()->toDateString();

        $this->trainingSession($ana, $date, ['title' => 'Treino da Ana', 'sport_id' => $corrida->id]);
        $this->trainingSession($bruno, $date, [
            'title' => 'Treino do Bruno',
            'sport_id' => $ciclismo->id,
            'status' => TrainingSessionStatus::COMPLETED,
        ]);

        $this->actingAs($teacher->user);

        // Filtro por aluno.
        Livewire::test(Agenda::class)
            ->set('filterStudent', (string) $ana->id)
            ->assertSee('Treino da Ana')
            ->assertDontSee('Treino do Bruno');

        // Filtro por modalidade.
        Livewire::test(Agenda::class)
            ->set('filterSport', (string) $ciclismo->id)
            ->assertSee('Treino do Bruno')
            ->assertDontSee('Treino da Ana');

        // Filtro por status.
        Livewire::test(Agenda::class)
            ->set('filterStatus', TrainingSessionStatus::COMPLETED->value)
            ->assertSee('Treino do Bruno')
            ->assertDontSee('Treino da Ana');

        // Limpar filtros volta a mostrar tudo.
        Livewire::test(Agenda::class)
            ->set('filterStudent', (string) $ana->id)
            ->call('clearFilters')
            ->assertSee('Treino da Ana')
            ->assertSee('Treino do Bruno');
    }

    public function test_agenda_shows_empty_states(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();
        $other = Student::factory()->forTeacher($teacher)->create();

        $this->trainingSession($student, now()->startOfWeek()->addDay()->toDateString(), ['title' => 'Treino existente']);

        $this->actingAs($teacher->user);

        // Semana vazia (nenhum treino no recorte).
        Livewire::test(Agenda::class)
            ->call('nextWeek')
            ->assertSee('Nenhum treino agendado nesta semana')
            ->assertDontSee('Treinos na semana');

        // Filtros que não encontram nada (mas existe treino na semana).
        Livewire::test(Agenda::class)
            ->set('filterStudent', (string) $other->id)
            ->assertSee('Nenhum treino encontrado com esses filtros')
            ->assertSee('Limpar filtros');
    }

    public function test_agenda_does_not_leak_other_tenant_data(): void
    {
        $teacher = Teacher::factory()->create();
        Student::factory()->forTeacher($teacher)->create(['name' => 'Aluno do professor A']);

        $other = Teacher::factory()->create();
        $otherStudent = Student::factory()->forTeacher($other)->create(['name' => 'Aluno do professor B']);
        $this->trainingSession($otherStudent, now()->startOfWeek()->addDay()->toDateString(), ['title' => 'Treino do professor B']);

        $this->actingAs($teacher->user);

        Livewire::test(Agenda::class)
            ->assertDontSee('Aluno do professor B')
            ->assertDontSee('Treino do professor B')
            ->assertSee('Nenhum treino agendado nesta semana');
    }

    public function test_agenda_is_teacher_only(): void
    {
        $this->seed(ConfigTableSeeder::class);

        $teacher = Teacher::factory()->create();
        $admin = User::factory(['role' => 'admin'])->create();

        $this->get(route('agenda.index'))->assertRedirect(route('login'));

        $this->actingAs($teacher->user);
        $this->get(route('agenda.index'))->assertOk();

        // O admin não tem tenant/agenda própria.
        $this->actingAs($admin);
        $this->get(route('agenda.index'))->assertForbidden();
    }

    public function test_agenda_service_computes_week_totals(): void
    {
        // Semana congelada numa quarta-feira: terça já passou (o `is_past` do
        // dia não pode depender do dia da semana em que a suíte roda).
        Carbon::setTestNow(now()->startOfWeek()->addDays(2)->setTime(10, 0));

        $teacher = Teacher::factory()->create();
        $ana = Student::factory()->forTeacher($teacher)->create();
        $bruno = Student::factory()->forTeacher($teacher)->create();

        $monday = now()->startOfWeek()->toDateString();

        $this->trainingSession($ana, $monday, [
            'estimated_duration' => 3600,
            'distance' => 10000,
            'status' => TrainingSessionStatus::COMPLETED,
        ]);
        $this->trainingSession($bruno, now()->startOfWeek()->addDays(2)->toDateString(), [
            'estimated_duration' => 1800,
            'distance' => null,
            'status' => TrainingSessionStatus::PLANNED,
        ]);
        $this->trainingSession($ana, now()->startOfWeek()->addDays(4)->toDateString(), [
            'estimated_duration' => 2700,
            'distance' => 5000,
            'status' => TrainingSessionStatus::SKIPPED,
        ]);

        // Fora da semana.
        $this->trainingSession($ana, now()->subWeek()->startOfWeek()->toDateString(), ['estimated_duration' => 9999]);

        $this->actingAs($teacher->user);
        $week = app(AgendaService::class)->week(now()->startOfWeek());

        $this->assertCount(7, $week['days']);
        $this->assertSame(1, $week['days']->first()['date']->dayOfWeekIso);

        $totals = $week['totals'];
        $this->assertSame(3, $totals['sessions']);
        $this->assertSame(2, $totals['students']);
        $this->assertSame(1, $totals['completed']);
        $this->assertSame(1, $totals['planned']);
        $this->assertSame(1, $totals['skipped']);
        $this->assertSame(0, $totals['cancelled']);
        $this->assertSame(8100, $totals['duration_seconds']);
        $this->assertSame(15000, $totals['distance_meters']);

        // Agrupamento por dia: segunda tem 1, terça nenhuma, quarta 1.
        $this->assertCount(1, $week['days'][0]['sessions']);
        $this->assertCount(0, $week['days'][1]['sessions']);
        $this->assertCount(1, $week['days'][2]['sessions']);
        $this->assertTrue($week['days'][1]['is_past']);
    }

    public function test_agenda_service_ignores_other_tenants(): void
    {
        $teacher = Teacher::factory()->create();

        $other = Teacher::factory()->create();
        $otherStudent = Student::factory()->forTeacher($other)->create();
        $this->trainingSession($otherStudent, now()->startOfWeek()->toDateString(), []);

        $this->actingAs($teacher->user);
        $week = app(AgendaService::class)->week(now()->startOfWeek());

        $this->assertSame(0, $week['totals']['sessions']);
    }
}
