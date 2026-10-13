<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Cadastro de treinadores (tenants) pela plataforma.
 *
 * O tenant nasce aqui: users.role = teacher + perfil em teachers.
 * Também garante o perfil em caminhos legados (tela de usuários do starter),
 * para nunca existir "treinador" sem tenant (painel ficaria vazio).
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
     * Atualiza a landing page pública do treinador (dados públicos do tenant).
     *
     * Slug vazio é gerado do nome (unicidade + palavras reservadas no model);
     * slug informado passou pela Form Request (unique/reservado/formato).
     * Nada de alunos/treinos/pagamentos aqui: a landing é só conteúdo do perfil.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateLanding(Teacher $teacher, array $data): Teacher
    {
        $teacher->slug = ! empty($data['slug'])
            ? Str::slug($data['slug'])
            : Teacher::makeUniqueSlug($teacher->name, $teacher->id);
        $teacher->tagline = $data['tagline'] ?? null;
        $teacher->bio = $data['bio'] ?? null;
        $teacher->whatsapp = $data['whatsapp'] ?? null;
        $teacher->public_profile = (bool) ($data['public_profile'] ?? false);

        // Identidade visual (caminhos finais vindos do upload do componente).
        $teacher->logo = $data['logo'] ?? null;
        $teacher->favicon = $data['favicon'] ?? null;
        $teacher->metaimg = $data['metaimg'] ?? null;

        // Redes sociais ('' vira null — a Form Request não passa por middleware
        // de conversão de strings vazias).
        $teacher->facebook = ($data['facebook'] ?? '') ?: null;
        $teacher->twitter = ($data['twitter'] ?? '') ?: null;
        $teacher->instagram = ($data['instagram'] ?? '') ?: null;
        $teacher->youtube = ($data['youtube'] ?? '') ?: null;
        $teacher->linkedin = ($data['linkedin'] ?? '') ?: null;

        // Endereço (exibição pública condicionada a display_address).
        $teacher->display_address = (bool) ($data['display_address'] ?? false);
        $teacher->zipcode = ($data['zipcode'] ?? '') ?: null;
        $teacher->street = ($data['street'] ?? '') ?: null;
        $teacher->number = ($data['number'] ?? '') ?: null;
        $teacher->complement = ($data['complement'] ?? '') ?: null;
        $teacher->neighborhood = ($data['neighborhood'] ?? '') ?: null;
        $teacher->state = ! empty($data['state']) ? strtoupper(trim($data['state'])) : null;
        $teacher->city = ($data['city'] ?? '') ?: null;

        $teacher->save();

        return $teacher;
    }

    /**
     * Ativa/desativa o tenant: perfil (teachers.active) e login (users.status)
     * mudam juntos — treinador inativo não entra no painel.
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
