<?php

namespace App\Livewire\Dashboard\Professores;

use App\Http\Requests\StoreTeacherRequest;
use App\Http\Requests\UpdateTeacherRequest;
use App\Models\Teacher;
use App\Services\TeacherService;
use App\Traits\ValidatesWithFormRequest;
use App\Traits\WithToastr;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Cadastro/edição de professor (tenant) — exclusivo do admin da plataforma.
 */
class ProfessorForm extends Component
{
    use ValidatesWithFormRequest, WithToastr;

    public int $teacherId = 0;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $specialty = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount($teacher = null): void
    {
        if (is_string($teacher) || is_int($teacher)) {
            $teacher = Teacher::find($teacher) ?? abort(404);
        }

        if ($teacher instanceof Teacher) {
            Gate::authorize('update', $teacher);

            $this->teacherId = $teacher->id;
            $this->name = $teacher->name;
            $this->email = $teacher->user->email;
            $this->phone = $teacher->phone ?? '';
            $this->specialty = $teacher->specialty ?? '';

            return;
        }

        Gate::authorize('create', Teacher::class);
    }

    public function save(TeacherService $service)
    {
        $isEdit = $this->teacherId > 0;

        $data = [
            'user_id' => $isEdit ? Teacher::find($this->teacherId)?->user_id : null,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
            'specialty' => $this->specialty ?: null,
            'password' => $this->password ?: null,
            'password_confirmation' => $this->password_confirmation,
        ];

        if ($isEdit) {
            $teacher = Teacher::find($this->teacherId) ?? abort(404);
            Gate::authorize('update', $teacher);

            $validated = $this->validateWith(UpdateTeacherRequest::class, $data);
            $service->update($teacher, $validated);

            $this->toastSuccess('Professor atualizado com sucesso.');
        } else {
            Gate::authorize('create', Teacher::class);

            $validated = $this->validateWith(StoreTeacherRequest::class, $data);
            $service->store($validated);

            $this->toastSuccess('Professor cadastrado com sucesso.');
        }

        return $this->redirect(route('professors.index'));
    }

    public function cancel()
    {
        return $this->redirect(route('professors.index'));
    }

    #[Title('Formulário de Professor')]
    public function render()
    {
        return view('livewire.dashboard.professores.form', [
            'isEdit' => $this->teacherId > 0,
        ]);
    }
}
