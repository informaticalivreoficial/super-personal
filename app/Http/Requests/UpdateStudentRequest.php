<?php

namespace App\Http\Requests;

use App\Enums\StudentGender;
use App\Enums\StudentLevel;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $teacherId = auth()->user()?->resolveTenantId();
        // Rota (API) usa model binding; componente Livewire envia `student_id` no payload.
        $studentId = $this->route('student')?->id ?? $this->input('student_id');
        $currentUserId = $studentId ? Student::find($studentId)?->user_id : null;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes', 'required', 'email', 'max:255',
                Rule::unique('students', 'email')
                    ->where('teacher_id', $teacherId)
                    ->whereNull('deleted_at')
                    ->ignore($studentId),
                // O e-mail do aluno será o login no app (ignora a conta própria).
                function (string $attribute, mixed $value, \Closure $fail) use ($currentUserId): void {
                    $query = User::where('email', $value);
                    if ($currentUserId) {
                        $query->where('id', '!=', $currentUserId);
                    }
                    if ($query->exists()) {
                        $fail('Este e-mail já está em uso por outra conta de acesso.');
                    }
                },
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::enum(StudentGender::class)],
            'document' => ['nullable', 'string', 'max:30'],
            'profile_photo' => ['nullable', 'string', 'max:255'],
            'height' => ['nullable', 'integer', 'min:50', 'max:260'],
            'initial_weight' => ['nullable', 'numeric', 'min:20', 'max:400'],
            'current_weight' => ['nullable', 'numeric', 'min:20', 'max:400'],
            'target_weight' => ['nullable', 'numeric', 'min:20', 'max:400'],
            'goal' => ['nullable', 'string', 'max:255'],
            'fitness_level' => ['nullable', Rule::enum(StudentLevel::class)],
            'training_experience' => ['nullable', 'string', 'max:100'],
            'available_days' => ['nullable', 'array'],
            'available_days.*' => ['integer', 'min:1', 'max:7'],
            'observations' => ['nullable', 'string'],
            'active' => ['sometimes', 'boolean'],
            'started_at' => ['nullable', 'date'],
        ];
    }
}
