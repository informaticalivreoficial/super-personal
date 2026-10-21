<?php

namespace App\Livewire\Dashboard\TrainingTemplates;

use App\Models\Student;
use App\Models\TrainingPlanTemplate;
use App\Services\TrainingPlanService;
use App\Traits\WithToastr;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Biblioteca de modelos (templates) de periodização do treinador.
 *
 * Um modelo guarda semanas/sessões/composição em offsets de dias e pode ser
 * aplicado a qualquer aluno, ancorado na data de início escolhida.
 */
#[Layout('components.layouts.app')]
#[Title('Modelos de treino')]
class TemplateIndex extends Component
{
    use WithPagination, WithToastr;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    // Modal de aplicação
    public ?int $applyTemplateId = null;

    public $applyStudentId = null;

    public $applyStartDate = '';

    public $applyName = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', TrainingPlanTemplate::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openApply($templateId): void
    {
        $template = TrainingPlanTemplate::find($templateId) ?? abort(404);
        Gate::authorize('view', $template);

        $this->resetValidation();
        $this->applyTemplateId = $template->id;
        $this->applyStudentId = null;
        $this->applyStartDate = now()->toDateString();
        $this->applyName = $template->name;
    }

    public function closeApply(): void
    {
        $this->reset('applyTemplateId', 'applyStudentId', 'applyStartDate', 'applyName');
        $this->resetValidation();
    }

    public function apply(TrainingPlanService $service): void
    {
        $this->validate([
            'applyTemplateId' => ['required', 'integer'],
            'applyStudentId' => ['required'],
            'applyStartDate' => ['required', 'date'],
            'applyName' => ['nullable', 'string', 'max:255'],
        ], [
            'applyStudentId.required' => 'Selecione o aluno que vai receber o plano.',
            'applyStartDate.required' => 'Informe a data de início.',
            'applyStartDate.date' => 'Informe uma data de início válida.',
            'applyName.max' => 'O nome do plano não pode ter mais de 255 caracteres.',
        ], [
            'applyStudentId' => 'aluno',
            'applyStartDate' => 'data de início',
            'applyName' => 'nome do plano',
        ]);

        $template = TrainingPlanTemplate::find($this->applyTemplateId) ?? abort(404);
        Gate::authorize('view', $template);

        // Escopo do tenant: aluno de outro treinador não é encontrado.
        $student = Student::find($this->applyStudentId);

        if (! $student) {
            $this->addError('applyStudentId', 'Selecione um aluno válido.');

            return;
        }

        $plan = $service->applyTemplate(
            $template,
            $student,
            $this->applyStartDate,
            $this->applyName ?: null
        );

        $this->closeApply();
        $this->toastSuccess('Modelo aplicado como rascunho. Revise as datas antes de publicar.');

        $this->redirectRoute('plans.show', $plan);
    }

    public function delete($templateId, TrainingPlanService $service): void
    {
        $template = TrainingPlanTemplate::find($templateId) ?? abort(404);
        Gate::authorize('delete', $template);

        $service->deleteTemplate($template);

        if ($this->applyTemplateId === $template->id) {
            $this->closeApply();
        }

        $this->toastSuccess('Modelo excluído.');
    }

    public function render()
    {
        $templates = TrainingPlanTemplate::query()
            ->with('sport')
            ->when($this->search !== '', function ($query) {
                $term = '%'.$this->search.'%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->orderByDesc('id')
            ->paginate(12);

        return view('livewire.dashboard.training-templates.index', [
            'templates' => $templates,
            'students' => $this->applyTemplateId
                ? Student::orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }
}
