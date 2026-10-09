<?php

namespace App\Http\Requests;

use App\Enums\StudentGender;
use App\Enums\StudentLevel;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $teacherId = auth()->user()?->resolveTenantId();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('students', 'email')
                    ->where('teacher_id', $teacherId)
                    ->whereNull('deleted_at'),
                // O e-mail do aluno será o login no app — detecta conflito já aqui.
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (User::where('email', $value)->exists()) {
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

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Este aluno já está cadastrado com esse e-mail.',
            'available_days.*' => 'Os dias disponíveis devem ser números de 1 (domingo) a 7 (sábado).',
        ];
    }
}
