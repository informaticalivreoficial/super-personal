<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Conta do próprio usuário (página "Meu perfil" no painel).
 *
 * Caminho único de escrita dos dados da conta: nome/e-mail/senha ficam em
 * `users`; quando o usuário é treinador, o nome e o telefone também são
 * sincronizados com o perfil `teachers` (fonte do que aparece na landing
 * e no cadastro do tenant). Admin não tem perfil de treinador — só o user.
 */
class ProfileService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->phone = $data['phone'] ?? null;

            if (! empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }

            $user->save();

            // Sincroniza o perfil do tenant (não mexe em slug nem em
            // landing_title — são conteúdos editados em LandingForm).
            $teacher = $user->teacher;

            if ($teacher !== null) {
                $teacher->name = $data['name'];
                $teacher->phone = $data['phone'] ?? null;
                $teacher->save();
            }

            return $user;
        });
    }
}
