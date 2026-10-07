<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;

class StudentService
{
    public function store(array $data): Student
    {
        // Admin da plataforma não tem tenant: CRUD de alunos é do professor.
        abort_unless($this->teacherId() > 0, 403);

        $student = new Student;
        $student->teacher_id = $this->teacherId();
        $student->fill($data);
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

    private function teacherId(): int
    {
        return (int) auth()->user()->resolveTenantId();
    }
}
