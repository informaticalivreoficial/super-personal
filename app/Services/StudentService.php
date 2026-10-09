<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class StudentService
{
    /** Alfabeto sem caracteres ambíguos (L/I/O/0/1) — o aluno digita o código. */
    private const INVITE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): Student
    {
        // Admin da plataforma não tem tenant: CRUD de alunos é do treinador.
        abort_unless($this->teacherId() > 0, 403);

        $student = new Student;
        $student->teacher_id = $this->teacherId();
        $student->fill($data);

        if (! $student->user_id) {
            $student->invite_code = $this->generateInviteCode();
        }

        $student->save();

        return $student;
    }

    public function update(Student $student, array $data): Student
    {
        $student->fill($data);
        $student->save();

        return $student;
    }

    /**
     * Exclusão lógica: preserva histórico (treinos, pagamentos).
     * O acesso do aluno à API é bloqueado ao desativar a conta de login.
     */
    public function delete(Student $student): void
    {
        $student->delete();

        if ($student->user_id) {
            User::whereKey($student->user_id)->update(['status' => 0]);
        }
    }

    /**
     * Gera um novo código de convite (aluno ainda sem conta de acesso).
     *
     * @return string novo código gerado
     */
    public function regenerateInviteCode(Student $student): string
    {
        if ($student->user_id) {
            throw ValidationException::withMessages([
                'invite_code' => 'Este aluno já possui conta de acesso.',
            ]);
        }

        $student->invite_code = $this->generateInviteCode();
        $student->save();

        return $student->invite_code;
    }

    /**
     * Registro do aluno no app com o código de convite do treinador.
     *
     * Cria users.role = student (e-mail = cadastro do aluno), vincula o
     * perfil students.user_id e consome o código. Retorna o usuário criado
     * — o controller emite o token Sanctum.
     *
     * @param  array<string, mixed>  $data
     */
    public function registerWithInvite(array $data): User
    {
        $code = strtoupper(trim((string) ($data['invite_code'] ?? '')));
        $student = Student::query()->where('invite_code', $code)->first();

        if (! $student) {
            throw ValidationException::withMessages([
                'invite_code' => 'Código de convite inválido.',
            ]);
        }

        if ($student->user_id) {
            throw ValidationException::withMessages([
                'invite_code' => 'Este aluno já possui conta de acesso. Faça login.',
            ]);
        }

        if (! $student->active) {
            throw ValidationException::withMessages([
                'invite_code' => 'Cadastro de aluno inativo. Fale com o treinador.',
            ]);
        }

        if (User::where('email', $student->email)->exists()) {
            throw ValidationException::withMessages([
                'invite_code' => 'Este e-mail já está em uso por outra conta. Solicite ao treinador atualizar o cadastro do aluno.',
            ]);
        }

        return DB::transaction(function () use ($student, $data) {
            // `role`/`status` não são fillable (mass assignment).
            $user = new User;
            $user->name = $student->name;
            $user->email = $student->email;
            $user->password = Hash::make($data['password']);
            $user->status = 1;
            $user->role = UserRole::STUDENT;
            $user->email_verified_at = now();
            $user->save();

            $student->user_id = $user->id;
            $student->invite_code = null;
            $student->save();

            return $user;
        });
    }

    private function generateInviteCode(): string
    {
        do {
            $code = '';

            for ($i = 0; $i < 8; $i++) {
                $code .= self::INVITE_ALPHABET[random_int(0, strlen(self::INVITE_ALPHABET) - 1)];
            }
        } while (Student::withTrashed()->where('invite_code', $code)->exists());

        return $code;
    }

    private function teacherId(): int
    {
        return (int) auth()->user()->resolveTenantId();
    }
}
