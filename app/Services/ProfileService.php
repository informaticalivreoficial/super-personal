<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Conta do próprio usuário (página "Meu perfil" no painel).
 *
 * Caminho único de escrita dos dados da conta: dados pessoais, documentos,
 * contato e endereço ficam em `users` (mutators do model tratam formatação:
 * birthday d/m/Y → Y-m-d, telefones, CPF e CEP limpos); quando o usuário é
 * treinador, o nome e o telefone principal são sincronizados com o perfil
 * `teachers` (fonte do que aparece na landing e no cadastro do tenant).
 * Admin não tem perfil de treinador — só o user.
 */
class ProfileService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            // Dados pessoais
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->additional_email = ($data['additional_email'] ?? '') ?: null;
            $user->birthday = ($data['birthday'] ?? '') ?: null;

            // Documentos
            $user->cpf = ($data['cpf'] ?? '') ?: null;
            $user->rg = ($data['rg'] ?? '') ?: null;
            $user->rg_expedition = ($data['rg_expedition'] ?? '') ?: null;

            // Contato
            $user->phone = ($data['phone'] ?? '') ?: null;
            $user->cell_phone = ($data['cell_phone'] ?? '') ?: null;
            $user->whatsapp = ($data['whatsapp'] ?? '') ?: null;

            // Endereço
            $user->zipcode = ($data['zipcode'] ?? '') ?: null;
            $user->street = ($data['street'] ?? '') ?: null;
            $user->number = ($data['number'] ?? '') ?: null;
            $user->complement = ($data['complement'] ?? '') ?: null;
            $user->neighborhood = ($data['neighborhood'] ?? '') ?: null;
            $user->state = ! empty($data['state']) ? strtoupper(trim($data['state'])) : null;
            $user->city = ($data['city'] ?? '') ?: null;

            // Foto de perfil (caminho final vindo do upload do componente;
            // a chave pode não existir quando o form não mexeu no avatar).
            if (array_key_exists('avatar', $data)) {
                $user->avatar = $data['avatar'];
            }

            if (! empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }

            $user->save();

            // Sincroniza o perfil do tenant (não mexe em slug nem em
            // landing_title — são conteúdos editados em LandingForm).
            $teacher = $user->teacher;

            if ($teacher !== null) {
                $teacher->name = $data['name'];
                $teacher->phone = ($data['phone'] ?? '') ?: null;
                // Chave Pix usada nos repasses das cobranças online.
                $teacher->pix_key = ($data['pix_key'] ?? '') ?: null;
                $teacher->pix_key_type = ($data['pix_key_type'] ?? '') ?: null;
                $teacher->save();
            }

            return $user;
        });
    }
}
