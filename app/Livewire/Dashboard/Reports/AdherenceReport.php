<?php

namespace App\Livewire\Dashboard\Reports;

use App\Enums\TrainingSessionStatus;
use App\Models\Student;
use App\Services\AdherenceReportService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Relatório de aderência do treinador: concluídas, puladas e não realizadas
 * no período, com ranking por aluno e modalidade e exportação em CSV.
 *
 * Competência = data agendada da sessão (ver AdherenceReportService).
 */
#[Layout('components.layouts.app')]
#[Title('Relatório de Aderência')]
class AdherenceReport extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $period = 'month';

    public string $from = '';

    public string $to = '';

    public $filterStudentId = null;

    public $filterSportId = null;

    public function mount(AdherenceReportService $service): void
    {
        Gate::authorize('viewAny', Student::class);

        $this->applyPeriod('month', $service);
    }

    public function setPeriod(string $period, AdherenceReportService $service): void
    {
        if (! array_key_exists($period, AdherenceReportService::PERIODS)) {
            return;
        }

        $this->applyPeriod($period, $service);
    }

    public function updatedFrom(): void
    {
        $this->period = 'custom';
        $this->resetPage();
    }

    public function updatedTo(): void
    {
        $this->period = 'custom';
        $this->resetPage();
    }

    public function updatedFilterStudentId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterSportId(): void
    {
        $this->resetPage();
    }

    public function clearFilters(AdherenceReportService $service): void
    {
        $this->filterStudentId = null;
        $this->filterSportId = null;
        $this->applyPeriod('month', $service);
    }

    /** URL do CSV com os filtros atuais (download fora do Livewire). */
    public function exportUrl(AdherenceReportService $service): string
    {
        return route('reports.adherence.export', array_filter([
            'from' => $this->from,
            'to' => $this->to,
            'student_id' => $this->filterStudentId,
            'sport_id' => $this->filterSportId,
        ], fn ($value) => filled($value)));
    }

    private function applyPeriod(string $period, AdherenceReportService $service): void
    {
        $range = $service->periodRange($period);

        $this->period = $period;
        $this->from = $range['from'];
        $this->to = $range['to'];
        $this->resetPage();
    }

    public function render(AdherenceReportService $service)
    {
        $filters = $service->filters([
            'from' => $this->from,
            'to' => $this->to,
            'student_id' => $this->filterStudentId,
            'sport_id' => $this->filterSportId,
        ]);

        $sessions = $service->sessions($filters);

        return view('livewire.dashboard.reports.adherence', [
            'filters' => $filters,
            'summary' => $service->summary($sessions),
            'byStudent' => $service->byStudent($sessions),
            'bySport' => $service->bySport($sessions),
            'sessions' => $service->query($filters)
                ->with(['student', 'sport'])
                ->orderByDesc('scheduled_date')
                ->orderBy('id')
                ->paginate(15),
            'students' => Student::query()->orderBy('name')->get(['id', 'name']),
            'sports' => $service->sportOptions(),
            'periods' => AdherenceReportService::PERIODS,
            'exportUrl' => $this->exportUrl($service),
            'statusLabels' => TrainingSessionStatus::labels(),
        ]);
    }
}
