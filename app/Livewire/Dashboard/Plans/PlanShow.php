<?php

namespace App\Livewire\Dashboard\Plans;

use App\Enums\TrainingPlanStatus;
use App\Enums\TrainingSessionStatus;
use App\Enums\TrainingWeekStatus;
use App\Http\Requests\StoreTrainingSessionRequest;
use App\Http\Requests\StoreWeekRequest;
use App\Http\Requests\UpdateTrainingSessionRequest;
use App\Models\Sport;
use App\Models\Student;
use App\Models\TrainingPlan;
use App\Models\TrainingSession;
use App\Models\TrainingWeek;
use App\Services\TrainingPlanService;
use App\Services\TrainingSessionService;
use App\Traits\ValidatesWithFormRequest;
use App\Traits\WithToastr;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Detalhe do plano: semanas e sessões (CRUD completo).
 */
#[Layout('components.layouts.app')]
class PlanShow extends Component
{
    use ValidatesWithFormRequest, WithToastr;

    public TrainingPlan $plan;

    // Estado do formulário de semana
    public bool $showWeekForm = false;

    public $editingWeekId = null;

    public $week = [
        'week_number' => '',
        'name' => '',
        'start_date' => '',
        'end_date' => '',
        'objective' => '',
    ];

    // Estado do formulário de sessão
    public $expandedWeekId = null;

    // Sessão com a composição (itens) expandida
    public $expandedItemsId = null;

    public bool $showSessionForm = false;

    public $editingSessionId = null;

    public $sessionWeekId = null;

    // Estado do formulário de duplicação (plano → outro aluno)
    public bool $showDuplicateForm = false;

    public $duplicateStudentId = null;

    public $duplicateName = '';

    // Estado do formulário "Salvar como modelo"
    public bool $showTemplateForm = false;

    public $templateName = '';

    public $templateDescription = '';

    public $session = [
        'sport_id' => '',
        'title' => '',
        'scheduled_date' => '',
        'description' => '',
        'estimated_duration' => '',
        'distance' => '',
        'intensity' => '',
        'target_pace' => '',
        'instructions' => '',
        'coach_notes' => '',
    ];

    /**
     * @param  TrainingPlan|string|int|null  $plan  model (teste), id da rota ou null.
     *                                              O tipo não é declarado de propósito: com `TrainingPlan` o container do
     *                                              Livewire instancia um model vazio quando o parâmetro não vem da rota.
     */
    public function mount($plan = null): void
    {
        if (is_string($plan) || is_int($plan)) {
            // Escopo do tenant aplicado: id de outro treinador → null → 404.
            $plan = TrainingPlan::find($plan) ?? abort(404);
        }

        abort_unless($plan instanceof TrainingPlan, 404);

        Gate::authorize('view', $plan);

        $this->plan = $plan;
    }

    // *********************** Modelo de treino *************************************/

    /**
     * Abre o formulário que transforma este plano em modelo reaplicável.
     */
    public function openTemplateForm(): void
    {
        Gate::authorize('view', $this->plan);

        $this->resetTemplateForm();
        $this->templateName = $this->plan->name.' (modelo)';
        $this->templateDescription = $this->plan->description ?? '';
        $this->showTemplateForm = true;
    }

    public function closeTemplateForm(): void
    {
        $this->resetTemplateForm();
    }

    /**
     * Salva a periodização (semanas → sessões → composição) como modelo.
     * O plano original não muda; o modelo passa a aparecer em "Modelos".
     */
    public function saveAsTemplate(TrainingPlanService $service): void
    {
        Gate::authorize('view', $this->plan);

        $validated = $this->validate([
            'templateName' => ['required', 'string', 'max:255'],
            'templateDescription' => ['nullable', 'string', 'max:1000'],
        ], [
            'templateName.required' => 'Dê um nome ao modelo.',
            'templateName.max' => 'O nome do modelo não pode ter mais de 255 caracteres.',
            'templateDescription.max' => 'A descrição não pode ter mais de 1000 caracteres.',
        ], [
            'templateName' => 'nome do modelo',
            'templateDescription' => 'descrição',
        ]);

        $service->saveAsTemplate($this->plan, [
            'name' => $validated['templateName'],
            'description' => $validated['templateDescription'] ?? null,
        ]);

        $this->resetTemplateForm();
        $this->toastSuccess('Modelo salvo. Aplique em outro aluno em “Modelos de treino”.');
    }

    // *********************** Semanas **********************************************/

    public function openWeekForm(): void
    {
        Gate::authorize('create', [TrainingWeek::class, $this->plan]);

        $nextNumber = (int) TrainingWeek::where('training_plan_id', $this->plan->id)->max('week_number') + 1;

        $this->resetWeekForm();
        $this->week['week_number'] = $nextNumber;
        $this->week['start_date'] = $this->plan->start_date?->format('Y-m-d');
        $this->week['end_date'] = $this->plan->end_date?->format('Y-m-d');
        $this->showWeekForm = true;
    }

    public function editWeek($weekId): void
    {
        $week = TrainingWeek::findOrFail($weekId);
        Gate::authorize('update', $week);

        $this->editingWeekId = $week->id;
        $this->week = [
            'week_number' => $week->week_number,
            'name' => $week->name,
            'start_date' => $week->start_date?->format('Y-m-d'),
            'end_date' => $week->end_date?->format('Y-m-d'),
            'objective' => $week->objective,
        ];
        $this->showWeekForm = true;
    }

    public function closeWeekForm(): void
    {
        $this->resetWeekForm();
    }

    public function saveWeek(TrainingPlanService $service): void
    {
        $data = $this->week;
        $data['plan_id'] = $this->plan->id;
        $data['week_id'] = $this->editingWeekId;

        $validated = $this->validateWith(StoreWeekRequest::class, $data, 'week');

        if ($this->editingWeekId) {
            $week = TrainingWeek::findOrFail($this->editingWeekId);
            Gate::authorize('update', $week);
            $service->updateWeek($week, $validated);
            $this->toastSuccess('Semana atualizada com sucesso.');
        } else {
            Gate::authorize('create', [TrainingWeek::class, $this->plan]);
            $service->storeWeek($this->plan, $validated);
            $this->toastSuccess('Semana criada com sucesso.');
        }

        $this->resetWeekForm();
    }

    public function deleteWeek($weekId, TrainingPlanService $service): void
    {
        $week = TrainingWeek::findOrFail($weekId);
        Gate::authorize('delete', $week);

        $service->deleteWeek($week);

        if ($this->expandedWeekId === $weekId) {
            $this->expandedWeekId = null;
            $this->closeSessionForm();
        }

        $this->expandedItemsId = null;
        $this->resetWeekForm();
        $this->toastSuccess('Semana excluída com sucesso.');
    }

    // *********************** Duplicação ********************************************/

    /**
     * Abre o formulário de duplicação do plano para outro aluno.
     */
    public function openDuplicateForm(): void
    {
        Gate::authorize('view', $this->plan);

        $this->resetDuplicateForm();
        $this->duplicateStudentId = $this->plan->student_id;
        $this->duplicateName = $this->plan->name.' (cópia)';
        $this->showDuplicateForm = true;
    }

    public function closeDuplicateForm(): void
    {
        $this->resetDuplicateForm();
    }

    /**
     * Copia o plano (semanas, sessões e composição) para o aluno escolhido.
     * A cópia nasce como rascunho — publicar continua sendo ação explícita.
     */
    public function duplicatePlan(TrainingPlanService $service): void
    {
        Gate::authorize('view', $this->plan);

        $this->validate([
            'duplicateStudentId' => ['required'],
            'duplicateName' => ['nullable', 'string', 'max:255'],
        ], [
            'duplicateStudentId.required' => 'Selecione o aluno que vai receber a cópia.',
            'duplicateName.max' => 'O nome do plano não pode ter mais de 255 caracteres.',
        ], [
            'duplicateStudentId' => 'aluno',
            'duplicateName' => 'nome',
        ]);

        // Escopo do tenant: aluno de outro treinador não é encontrado.
        $student = Student::find($this->duplicateStudentId);

        if (! $student) {
            $this->addError('duplicateStudentId', 'Selecione um aluno válido.');

            return;
        }

        $plan = $service->duplicate($this->plan, $student, $this->duplicateName ?: null);

        $this->resetDuplicateForm();
        $this->toastSuccess('Plano duplicado como rascunho. Revise as datas antes de publicar.');

        $this->redirectRoute('plans.show', $plan);
    }

    /**
     * Repete uma semana no fim do plano (microciclo novo, datas deslocadas).
     */
    public function duplicateWeek($weekId, TrainingPlanService $service): void
    {
        $week = TrainingWeek::findOrFail($weekId);
        Gate::authorize('view', $week);

        $newWeek = $service->duplicateWeek($week);

        $this->expandedItemsId = null;
        $this->closeSessionForm();
        $this->expandedWeekId = $newWeek->id;
        $this->toastSuccess('Semana duplicada para o fim do plano.');
    }

    // *********************** Sessões **********************************************/

    public function toggleSessions($weekId): void
    {
        $this->closeSessionForm();
        $this->expandedItemsId = null;
        $this->expandedWeekId = $this->expandedWeekId === $weekId ? null : $weekId;
    }

    /**
     * Abre/fecha a composição (itens) de uma sessão.
     */
    public function toggleItems($sessionId): void
    {
        $session = TrainingSession::findOrFail($sessionId);
        Gate::authorize('view', $session);

        if ($this->expandedItemsId === $sessionId) {
            $this->expandedItemsId = null;

            return;
        }

        $this->closeSessionForm();
        $this->expandedItemsId = $sessionId;
        // Garante que a semana da sessão esteja exposta (a composição vive no acordeão).
        $this->expandedWeekId = $session->training_week_id;
    }

    public function openSessionForm($weekId): void
    {
        $week = TrainingWeek::findOrFail($weekId);
        Gate::authorize('create', TrainingSession::class);

        $this->resetSessionForm();
        $this->expandedItemsId = null;
        $this->sessionWeekId = $week->id;
        $this->session['scheduled_date'] = $week->start_date?->format('Y-m-d');
        $this->showSessionForm = true;
        $this->expandedWeekId = $week->id;
    }

    public function editSession($sessionId): void
    {
        $session = TrainingSession::findOrFail($sessionId);
        Gate::authorize('update', $session);

        $this->resetSessionForm();
        $this->expandedItemsId = null;
        $this->editingSessionId = $session->id;
        $this->sessionWeekId = $session->training_week_id;
        $this->session = [
            'sport_id' => $session->sport_id,
            'title' => $session->title,
            'scheduled_date' => $session->scheduled_date?->format('Y-m-d'),
            'description' => $session->description,
            'estimated_duration' => $session->estimated_duration,
            'distance' => $session->distance,
            'intensity' => $session->intensity,
            'target_pace' => $session->target_pace,
            'instructions' => $session->instructions,
            'coach_notes' => $session->coach_notes,
        ];
        $this->showSessionForm = true;
        $this->expandedWeekId = $session->training_week_id;
    }

    public function closeSessionForm(): void
    {
        $this->resetSessionForm();
    }

    public function saveSession(TrainingSessionService $service): void
    {
        $week = TrainingWeek::findOrFail($this->sessionWeekId);

        if ($this->editingSessionId) {
            $session = TrainingSession::findOrFail($this->editingSessionId);
            Gate::authorize('update', $session);

            $validated = $this->validateWith(
                UpdateTrainingSessionRequest::class,
                $this->sessionData(),
                'session'
            );

            $service->update($session, $validated);
            $this->toastSuccess('Sessão atualizada com sucesso.');
        } else {
            Gate::authorize('create', TrainingSession::class);

            $validated = $this->validateWith(
                StoreTrainingSessionRequest::class,
                $this->sessionData(),
                'session'
            );

            $service->store($week, $validated);
            $this->toastSuccess('Sessão criada com sucesso.');
        }

        $this->resetSessionForm();
    }

    public function deleteSession($sessionId, TrainingSessionService $service): void
    {
        $session = TrainingSession::findOrFail($sessionId);
        Gate::authorize('delete', $session);

        $service->delete($session);

        if ($this->expandedItemsId === $sessionId) {
            $this->expandedItemsId = null;
        }

        $this->toastSuccess('Sessão excluída com sucesso.');
    }

    // *********************** Helpers **********************************************/

    private function resetWeekForm(): void
    {
        $this->showWeekForm = false;
        $this->editingWeekId = null;
        $this->week = [
            'week_number' => '',
            'name' => '',
            'start_date' => '',
            'end_date' => '',
            'objective' => '',
        ];
        $this->resetValidation();
    }

    private function resetSessionForm(): void
    {
        $this->showSessionForm = false;
        $this->editingSessionId = null;
        $this->sessionWeekId = null;
        $this->session = [
            'sport_id' => '',
            'title' => '',
            'scheduled_date' => '',
            'description' => '',
            'estimated_duration' => '',
            'distance' => '',
            'intensity' => '',
            'target_pace' => '',
            'instructions' => '',
            'coach_notes' => '',
        ];
        $this->resetValidation();
    }

    private function resetDuplicateForm(): void
    {
        $this->showDuplicateForm = false;
        $this->duplicateStudentId = null;
        $this->duplicateName = '';
        $this->resetValidation();
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionData(): array
    {
        return [
            'sport_id' => $this->session['sport_id'] ?: null,
            'title' => $this->session['title'],
            'description' => $this->session['description'] ?: null,
            'scheduled_date' => $this->session['scheduled_date'] ?: null,
            'estimated_duration' => $this->session['estimated_duration'] !== '' ? (int) $this->session['estimated_duration'] : null,
            'distance' => $this->session['distance'] !== '' ? (int) $this->session['distance'] : null,
            'intensity' => $this->session['intensity'] ?: null,
            'target_pace' => $this->session['target_pace'] ?: null,
            'instructions' => $this->session['instructions'] ?: null,
            'coach_notes' => $this->session['coach_notes'] ?: null,
        ];
    }

    #[Title('Detalhe do Plano')]
    private function resetTemplateForm(): void
    {
        $this->reset('showTemplateForm', 'templateName', 'templateDescription');
        $this->resetValidation(['templateName', 'templateDescription']);
    }

    public function render()
    {
        $this->plan->load(['student', 'weeks.sessions']);

        return view('livewire.dashboard.plans.show', [
            'plan' => $this->plan,
            'sports' => Sport::orderBy('name')->get(),
            'students' => $this->showDuplicateForm ? Student::orderBy('name')->get() : collect(),
            'planStatus' => TrainingPlanStatus::labels(),
            'weekStatus' => TrainingWeekStatus::labels(),
            'sessionStatus' => TrainingSessionStatus::labels(),
        ]);
    }
}
