<?php

namespace App\Livewire\Dashboard;

use App\Enums\TrainingSessionStatus;
use App\Models\Sport;
use App\Models\Student;
use App\Services\AgendaService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Agenda semanal do treinador: todos os treinos do tenant na semana,
 * agrupados por dia, com KPIs e filtros (aluno, modalidade e status).
 *
 * Somente leitura — a edição continua no plano do aluno (plans.show).
 */
#[Layout('components.layouts.app')]
#[Title('Agenda')]
class Agenda extends Component
{
    /** Segunda-feira da semana exibida (Y-m-d). */
    public string $referenceDate = '';

    public string $filterStudent = '';

    public string $filterSport = '';

    public string $filterStatus = '';

    public function mount(): void
    {
        // A rota é role:teacher (o admin não tem tenant/agenda própria).
        abort_unless(auth()->user()?->teacher, 403, 'Perfil de treinador não encontrado.');

        $this->referenceDate = Carbon::now()->startOfWeek()->toDateString();
    }

    public function previousWeek(): void
    {
        $this->referenceDate = $this->weekStart()->subWeek()->toDateString();
    }

    public function nextWeek(): void
    {
        $this->referenceDate = $this->weekStart()->addWeek()->toDateString();
    }

    public function currentWeek(): void
    {
        $this->referenceDate = Carbon::now()->startOfWeek()->toDateString();
    }

    public function clearFilters(): void
    {
        $this->reset('filterStudent', 'filterSport', 'filterStatus');
    }

    public function render(AgendaService $agenda)
    {
        $weekStart = $this->weekStart();
        $weekEnd = $weekStart->copy()->addDays(6);

        $week = $agenda->week($weekStart, [
            'student_id' => $this->filterStudent,
            'sport_id' => $this->filterSport,
            'status' => $this->filterStatus,
        ]);

        return view('livewire.dashboard.agenda', [
            'days' => $week['days'],
            'totals' => $week['totals'],
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'weekLabel' => $this->weekLabel($weekStart, $weekEnd),
            'isCurrentWeek' => $weekStart->isSameWeek(Carbon::now()),
            'students' => Student::orderBy('name')->get(['id', 'name']),
            'sports' => Sport::where('active', true)->orderBy('name')->get(['id', 'name']),
            'statuses' => TrainingSessionStatus::labels(),
            'hasFilters' => (bool) ($this->filterStudent || $this->filterSport || $this->filterStatus),
        ]);
    }

    private function weekStart(): Carbon
    {
        return Carbon::parse($this->referenceDate)->startOfWeek();
    }

    /**
     * "13 a 19 de outubro de 2026" — formato curto entre meses/anos.
     */
    private function weekLabel(Carbon $start, Carbon $end): string
    {
        if ($start->isSameMonth($end)) {
            return $start->translatedFormat('d').' a '.$end->translatedFormat('d \d\e F \d\e Y');
        }

        if ($start->isSameYear($end)) {
            return $start->translatedFormat('d \d\e F').' a '.$end->translatedFormat('d \d\e F \d\e Y');
        }

        return $start->translatedFormat('d \d\e F \d\e Y').' a '.$end->translatedFormat('d \d\e F \d\e Y');
    }
}
