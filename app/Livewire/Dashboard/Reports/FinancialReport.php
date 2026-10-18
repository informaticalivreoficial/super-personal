<?php

namespace App\Livewire\Dashboard\Reports;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Student;
use App\Services\FinancialReportService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Relatório financeiro do treinador: recebido, a receber e em atraso no
 * período, com quebra por mês/aluno e exportação em CSV.
 *
 * Competência = data de vencimento da cobrança (ver FinancialReportService).
 */
#[Layout('components.layouts.app')]
#[Title('Relatório Financeiro')]
class FinancialReport extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    /** Preset de período selecionado ('custom' quando o usuário digita datas). */
    public string $period = 'month';

    public string $from = '';

    public string $to = '';

    public $filterStudentId = null;

    public string $filterChannel = '';

    public string $filterStatus = '';

    public function mount(FinancialReportService $service): void
    {
        Gate::authorize('viewAny', Payment::class);

        $this->applyPeriod('month', $service);
    }

    public function setPeriod(string $period, FinancialReportService $service): void
    {
        if (! array_key_exists($period, FinancialReportService::PERIODS)) {
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

    public function updatedFilterChannel(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function clearFilters(FinancialReportService $service): void
    {
        $this->filterStudentId = null;
        $this->filterChannel = '';
        $this->filterStatus = '';

        $this->applyPeriod('month', $service);
    }

    /**
     * URL da exportação em CSV com os filtros atuais (o download é servido
     * fora do Livewire — ver FinancialReportExportController).
     */
    public function exportUrl(FinancialReportService $service): string
    {
        return route('reports.financial.export', array_filter([
            'from' => $this->from,
            'to' => $this->to,
            'student_id' => $this->filterStudentId,
            'channel' => $this->filterChannel,
            'status' => $this->filterStatus,
        ], fn ($value) => filled($value)));
    }

    /**
     * Aplica um preset de período ao intervalo do relatório.
     */
    private function applyPeriod(string $period, FinancialReportService $service): void
    {
        $range = $service->periodRange($period);

        $this->period = $period;
        $this->from = $range['from'];
        $this->to = $range['to'];
        $this->resetPage();
    }

    public function render(FinancialReportService $service)
    {
        $filters = $service->filters([
            'from' => $this->from,
            'to' => $this->to,
            'student_id' => $this->filterStudentId,
            'channel' => $this->filterChannel,
            'status' => $this->filterStatus,
        ]);

        return view('livewire.dashboard.reports.financial', [
            'filters' => $filters,
            'summary' => $service->summary($filters),
            'months' => $service->byMonth($filters),
            'topStudents' => $service->byStudent($filters),
            'payments' => $service->payments($filters)->paginate(15),
            'studentOptions' => Student::orderBy('name')->get(['id', 'name']),
            'channels' => PaymentChannel::labels(),
            'statuses' => PaymentStatus::labels(),
            'periods' => FinancialReportService::PERIODS,
            'exportUrl' => $this->exportUrl($service),
        ]);
    }
}
