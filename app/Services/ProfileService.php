<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use App\Notifications\EmailChangeCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Conta do próprio usuário (página "Meu perfil" no painel e no app).
 *
 * Caminho único de escrita dos dados da conta: dados pessoais, documentos,
 * contato e endereço ficam em `users` (mutators do model tratam formatação:
 * birthday d/m/Y → Y-m-d, telefones, CPF e CEP limpos); quando o usuário é
 * treinador, o nome e o telefone principal são sincronizados com o perfil
 * `teachers` (fonte do que aparece na landing e no cadastro do tenant).
 * Admin não tem perfil de treinador — só o user.
 *
 * A troca de **e-mail** e de **senha** também passa por aqui: o e-mail só
 * muda com o código confirmado (enviado ao novo endereço) e a senha nova
 * derruba as outras sessões.
 */
class ProfileService
{
    /** Validade do código de troca de e-mail (minutos). */
    public const EMAIL_CODE_TTL_MINUTES = 15;

    /** Tentativas erradas aceitas antes de exigir um novo pedido. */
    public const EMAIL_CODE_MAX_ATTEMPTS = 5;

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

    /**
     * Pede a troca do e-mail de acesso: o endereço novo fica em
     * `pending_email` e recebe um código de 6 dígitos. `users.email` não muda
     * enquanto o titular não confirmar — assim um endereço digitado errado
     * não tira o acesso de ninguém.
     */
    public function requestEmailChange(User $user, string $email): User
    {
        $email = mb_strtolower(trim($email));

        if ($email === mb_strtolower((string) $user->email)) {
            throw ValidationException::withMessages([
                'email' => 'Informe um e-mail diferente do atual.',
            ]);
        }

        $conflict = $this->emailConflict($user, $email);

        if ($conflict !== null) {
            throw ValidationException::withMessages(['email' => $conflict]);
        }

        // O código em texto puro só existe no e-mail; no banco vai o hash.
        $code = (string) random_int(100000, 999999);

        $user->pending_email = $email;
        $user->email_change_code = Hash::make($code);
        $user->email_change_expires_at = now()->addMinutes(self::EMAIL_CODE_TTL_MINUTES);
        $user->email_change_attempts = 0;
        $user->save();

        // Vai para o NOVO endereço: é a prova de que o titular tem a caixa nova.
        Notification::route('mail', $email)->notify(
            new EmailChangeCode($code, $user->name, self::EMAIL_CODE_TTL_MINUTES)
        );

        return $user;
    }

    /**
     * Confirma o código e efetiva a troca: `users.email` passa a ser o novo
     * endereço e o cadastro do aluno (`students.email`, criado pelo convite do
     * treinador) acompanha — os dois são o mesmo e-mail do aluno.
     */
    public function confirmEmailChange(User $user, string $code): User
    {
        if (! $user->hasPendingEmailChange()) {
            throw ValidationException::withMessages([
                'code' => 'Nenhuma troca de e-mail em andamento. Peça um novo código.',
            ]);
        }

        if ($user->email_change_expires_at === null || $user->email_change_expires_at->isPast()) {
            $user->clearEmailChange();
            $user->save();

            throw ValidationException::withMessages([
                'code' => 'O código expirou. Peça um novo código.',
            ]);
        }

        if ((int) $user->email_change_attempts >= self::EMAIL_CODE_MAX_ATTEMPTS) {
            throw ValidationException::withMessages([
                'code' => 'Muitas tentativas erradas. Peça um novo código.',
            ]);
        }

        if (! Hash::check($code, (string) $user->email_change_code)) {
            $user->email_change_attempts = (int) $user->email_change_attempts + 1;
            $user->save();

            throw ValidationException::withMessages([
                'code' => 'Código incorreto. Confira o e-mail que enviamos.',
            ]);
        }

        $email = (string) $user->pending_email;

        // Corrida: o endereço pode ter sido tomado entre o pedido e a
        // confirmação — por isso a checagem fica fora da transação (o pedido é
        // descartado e o aluno faz um novo).
        $conflict = $this->emailConflict($user, $email);

        if ($conflict !== null) {
            $user->clearEmailChange();
            $user->save();

            throw ValidationException::withMessages(['email' => $conflict]);
        }

        return DB::transaction(function () use ($user, $email) {
            $user->email = $email;
            // Endereço confirmado pelo código: fica registrado como verificado.
            $user->email_verified_at = now();
            $user->clearEmailChange();
            $user->save();

            $student = $user->student;

            if ($student !== null) {
                $student->email = $email;
                $student->save();
            }

            return $user;
        });
    }

    /** Desiste da troca de e-mail (limpa o pedido pendente). */
    public function cancelEmailChange(User $user): User
    {
        $user->clearEmailChange();
        $user->save();

        return $user;
    }

    /**
     * Troca a senha do próprio usuário. As outras sessões caem (token do app
     * é revogado); a sessão que pediu a troca continua valendo quando o id do
     * token é informado — é o aparelho do titular.
     */
    public function changePassword(User $user, string $password, ?int $keepTokenId = null): void
    {
        DB::transaction(function () use ($user, $password, $keepTokenId) {
            $user->password = Hash::make($password);
            $user->save();

            $tokens = $user->tokens();

            if ($keepTokenId !== null) {
                $tokens->whereKeyNot($keepTokenId)->delete();

                return;
            }

            $tokens->delete();
        });
    }

    /**
     * O endereço pertence a outra conta (`users`) ou a outro cadastro de aluno
     * (`students`, credencial de acesso criada pelo convite do treinador)?
     */
    private function emailConflict(User $user, string $email): ?string
    {
        $taken = User::query()
            ->where('email', $email)
            ->whereKeyNot($user->getKey())
            ->exists();

        if ($taken) {
            return 'Este e-mail já está em uso.';
        }

        $studentId = $user->student?->getKey();

        $takenByStudent = Student::query()
            ->withoutGlobalScope('tenant')
            ->where('email', $email)
            ->when($studentId, fn ($query) => $query->whereKeyNot($studentId))
            ->exists();

        if ($takenByStudent) {
            return 'Este e-mail já está em uso.';
        }

        return null;
    }
}
