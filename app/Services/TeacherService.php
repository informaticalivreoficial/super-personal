<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Cadastro de professores (tenants) pela plataforma.
 *
 * O tenant nasce aqui: users.role = teacher + perfil em teachers.
 * Também garante o perfil em caminhos legados (tela de usuários do starter),
 * para nunca existir "professor" sem tenant (painel ficaria vazio).
 */
class TeacherService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): Teacher
    {
        return DB::transaction(function () use ($data) {
            // `role`/`status` não são fillable (mass assignment).
            $user = new User;
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->password = Hash::make($data['password']);
            $user->status = 1;
            $user->role = UserRole::TEACHER;
            $user->save();

            return $this->makeProfile($user, $data);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Teacher $teacher, array $data): Teacher
    {
        return DB::transaction(function () use ($teacher, $data) {
            $user = $teacher->user;
            $user->name = $data['name'];
            $user->email = $data['email'];

            if (! empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }

            $user->save();

            $teacher->name = $data['name'];
            $teacher->phone = $data['phone'] ?? null;
            $teacher->specialty = $data['specialty'] ?? null;
            $teacher->save();

            return $teacher;
        });
    }

    /**
     * Ativa/desativa o tenant: perfil (teachers.active) e login (users.status)
     * mudam juntos — professor inativo não entra no painel.
     */
    public function toggleActive(Teacher $teacher): Teacher
    {
        return DB::transaction(function () use ($teacher) {
            $teacher->active = ! $teacher->active;
            $teacher->save();

            $user = $teacher->user;
            $user->status = $teacher->active ? 1 : 0;
            $user->save();

            return $teacher;
        });
    }

    /**
     * Garante o perfil do tenant para um user com papel teacher.
     * Usado pela tela legada de usuários (criação/edição), que não cria o perfil.
     */
    public function ensureProfile(User $user): Teacher
    {
        $existing = Teacher::query()->where('user_id', $user->id)->first();

        if ($existing) {
            return $existing;
        }

        return $this->makeProfile($user, ['name' => $user->name]);
    }

    /**
     * `user_id` não é fillable em Teacher (associação feita diretamente).
     *
     * @param  array<string, mixed>  $data
     */
    private function makeProfile(User $user, array $data): Teacher
    {
        $teacher = new Teacher;
        $teacher->user_id = $user->id;
        $teacher->name = $data['name'];
        $teacher->phone = $data['phone'] ?? null;
        $teacher->specialty = $data['specialty'] ?? null;
        $teacher->active = true;
        $teacher->save();

        return $teacher;
    }
}
