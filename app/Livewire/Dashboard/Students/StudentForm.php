<?php

namespace App\Livewire\Dashboard\Students;

use App\Enums\StudentGender;
use App\Enums\StudentLevel;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Student;
use App\Services\StudentService;
use App\Traits\ValidatesWithFormRequest;
use App\Traits\WithToastr;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Formulário único de criação/edição de aluno.
 * As rotas `students.create` e `students.edit` apontam para este componente.
 */
#[Layout('components.layouts.app')]
class StudentForm extends Component
{
    use ValidatesWithFormRequest, WithToastr;

    public $studentId = null;

    public $name = '';

    public $email = '';

    public $phone = '';

    public $birth_date = '';

    public $gender = '';

    public $document = '';

    public $height = '';

    public $initial_weight = '';

    public $current_weight = '';

    public $target_weight = '';

    public $goal = '';

    public $fitness_level = '';

    public $training_experience = '';

    public $available_days = [];

    public $observations = '';

    public $active = true;

    public $started_at = '';

    /**
     * @param  Student|string|int|null  $student  model (teste), id da rota ou null (criação).
     *                                            O tipo não é declarado de propósito: com `?Student` o container do Livewire
     *                                            instancia um model vazio no create e o policy derruba a render com 403.
     */
    public function mount($student = null): void
    {
        if (is_string($student) || is_int($student)) {
            // Escopo do tenant aplicado: id de outro professor → null → 404.
            $student = Student::find($student) ?? abort(404);
        }

        if ($student instanceof Student) {
            Gate::authorize('update', $student);

            $this->studentId = $student->id;
            $this->name = $student->name;
            $this->email = $student->email;
            $this->phone = $student->phone;
            $this->birth_date = $student->birth_date?->format('Y-m-d');
            $this->gender = $student->gender?->value;
            $this->document = $student->document;
            $this->height = $student->height;
            $this->initial_weight = $student->initial_weight;
            $this->current_weight = $student->current_weight;
            $this->target_weight = $student->target_weight;
            $this->goal = $student->goal;
            $this->fitness_level = $student->fitness_level?->value;
            $this->training_experience = $student->training_experience;
            $this->available_days = $student->available_days ?? [];
            $this->observations = $student->observations;
            $this->active = $student->active;
            $this->started_at = $student->started_at?->format('Y-m-d');

            return;
        }

        Gate::authorize('create', Student::class);
    }

    public function save(StudentService $service)
    {
        if ($this->studentId) {
            $student = Student::findOrFail($this->studentId);
            Gate::authorize('update', $student);

            $data = $this->formData();
            $data['student_id'] = $student->id;

            $validated = $this->normalize(
                $this->validateWith(UpdateStudentRequest::class, $data)
            );

            $service->update($student, $validated);

            session()->flash('toast', [
                'type' => 'success',
                'message' => 'Aluno atualizado com sucesso.',
            ]);

            return $this->redirectRoute('students.show', ['student' => $student->id]);
        }

        Gate::authorize('create', Student::class);

        $validated = $this->normalize(
            $this->validateWith(StoreStudentRequest::class, $this->formData())
        );

        $student = $service->store($validated);

        session()->flash('toast', [
            'type' => 'success',
            'message' => 'Aluno cadastrado com sucesso.',
        ]);

        return $this->redirectRoute('students.show', ['student' => $student->id]);
    }

    public function cancel()
    {
        if ($this->studentId) {
            return $this->redirectRoute('students.show', ['student' => $this->studentId]);
        }

        return $this->redirectRoute('students.index');
    }

    /**
     * Dados crus para validação (cast só após, em normalize()).
     *
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
            'birth_date' => $this->birth_date ?: null,
            'gender' => $this->gender ?: null,
            'document' => $this->document ?: null,
            'height' => $this->height === '' ? null : $this->height,
            'initial_weight' => $this->initial_weight === '' ? null : $this->initial_weight,
            'current_weight' => $this->current_weight === '' ? null : $this->current_weight,
            'target_weight' => $this->target_weight === '' ? null : $this->target_weight,
            'goal' => $this->goal ?: null,
            'fitness_level' => $this->fitness_level ?: null,
            'training_experience' => $this->training_experience ?: null,
            'available_days' => array_map('intval', (array) $this->available_days),
            'observations' => $this->observations ?: null,
            'active' => (bool) $this->active,
            'started_at' => $this->started_at ?: null,
            'student_id' => $this->studentId,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalize(array $validated): array
    {
        if (isset($validated['height'])) {
            $validated['height'] = (int) $validated['height'];
        }

        foreach (['initial_weight', 'current_weight', 'target_weight'] as $field) {
            if (isset($validated[$field])) {
                $validated[$field] = (float) $validated[$field];
            }
        }

        return $validated;
    }

    #[Title('Cadastro de Aluno')]
    public function render()
    {
        return view('livewire.dashboard.students.form', [
            'genders' => StudentGender::labels(),
            'levels' => StudentLevel::labels(),
            'isEdit' => (bool) $this->studentId,
        ]);
    }
}
