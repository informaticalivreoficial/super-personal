<?php

namespace App\Services;

use App\Enums\PayoutAccountType;
use App\Enums\PixKeyType;
use App\Models\Payout;
use App\Models\Teacher;
use App\Models\TeacherPayoutAccount;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Contas/chaves de recebimento do treinador (destino dos repasses).
 *
 * Caminho único de escrita: normaliza a chave/chave Pix conforme o tipo e
 * garante que exista sempre exatamente uma conta padrão por treinador — a
 * sugerida ao solicitar um saque. O `teacher_id` vem sempre do treinador
 * autenticado (nunca do request).
 */
class PayoutAccountService
{
    /** Contas do treinador (padrão primeiro). */
    public function accounts(Teacher $teacher): Collection
    {
        return $teacher->payoutAccounts()
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();
    }

    /** Conta padrão do treinador (ou null quando não há nenhuma). */
    public function defaultAccount(Teacher $teacher): ?TeacherPayoutAccount
    {
        return $teacher->defaultPayoutAccount();
    }

    public function store(Teacher $teacher, array $data): TeacherPayoutAccount
    {
        return DB::transaction(function () use ($teacher, $data) {
            $account = new TeacherPayoutAccount;
            // `teacher_id` fora do fillable: sempre setado pelo Service.
            $account->teacher_id = $teacher->id;

            $this->fill($account, $data);

            // Primeira conta do treinador sempre nasce padrão.
            $account->is_default = ! empty($data['is_default']) || ! $teacher->payoutAccounts()->exists();
            $account->save();

            if ($account->is_default) {
                $this->clearOtherDefaults($teacher, $account->id);
            }

            return $account;
        });
    }

    public function update(TeacherPayoutAccount $account, array $data): TeacherPayoutAccount
    {
        return DB::transaction(function () use ($account, $data) {
            $this->fill($account, $data);
            $account->is_default = ! empty($data['is_default']) ? true : $account->is_default;
            $account->save();

            $teacher = $account->teacher;

            if ($account->is_default) {
                $this->clearOtherDefaults($teacher, $account->id);
            } elseif (! $this->hasDefaultBesides($teacher, $account->id)) {
                // Nunca deixar o treinador sem conta padrão.
                $account->is_default = true;
                $account->save();
            }

            return $account;
        });
    }

    /** Torna a conta a padrão do treinador. */
    public function setDefault(TeacherPayoutAccount $account): TeacherPayoutAccount
    {
        return DB::transaction(function () use ($account) {
            $account->is_default = true;
            $account->save();
            $this->clearOtherDefaults($account->teacher, $account->id);

            return $account;
        });
    }

    /**
     * Remove a conta. Se era a padrão, promove a mais antiga das restantes.
     * Histórico de repasses é preservado (o `destination` fica congelado).
     */
    public function delete(TeacherPayoutAccount $account): void
    {
        DB::transaction(function () use ($account) {
            $teacher = $account->teacher;
            $wasDefault = $account->is_default;

            // Histórico preservado: o texto do destino fica congelado no Payout.
            // O vínculo é liberado na mão (não dependemos do `nullOnDelete` do
            // banco, que não é garantido em todos os drivers).
            Payout::withoutGlobalScope('tenant')
                ->where('payout_account_id', $account->id)
                ->update(['payout_account_id' => null]);

            $account->delete();

            if ($wasDefault && $teacher !== null) {
                $next = $teacher->payoutAccounts()->orderBy('id')->first();

                if ($next !== null) {
                    $next->is_default = true;
                    $next->save();
                }
            }
        });
    }

    /** Preenche os campos conforme o tipo (limpa o que não se aplica). */
    private function fill(TeacherPayoutAccount $account, array $data): void
    {
        $type = $data['type'] instanceof PayoutAccountType
            ? $data['type']
            : PayoutAccountType::from($data['type']);

        $account->type = $type;
        $account->label = $this->blankToNull($data['label'] ?? null);

        if ($type === PayoutAccountType::PIX) {
            $account->pix_key_type = $data['pix_key_type'] ?? null;
            $account->pix_key = $this->normalizePixKey($data['pix_key_type'] ?? null, $data['pix_key'] ?? null);

            $account->bank_name = null;
            $account->bank_code = null;
            $account->agency = null;
            $account->account = null;
            $account->account_digit = null;
            $account->account_type = null;
            $account->holder_name = null;
            $account->holder_document = null;

            return;
        }

        $account->bank_name = $this->blankToNull($data['bank_name'] ?? null);
        $account->bank_code = $this->blankToNull($data['bank_code'] ?? null);
        $account->agency = $this->blankToNull($data['agency'] ?? null);
        $account->account = $this->blankToNull($data['account'] ?? null);
        $account->account_digit = $this->blankToNull($data['account_digit'] ?? null);
        $account->account_type = $data['account_type'] ?? null;
        $account->holder_name = $this->blankToNull($data['holder_name'] ?? null);
        $account->holder_document = $this->onlyDigits($data['holder_document'] ?? null);

        $account->pix_key = null;
        $account->pix_key_type = null;
    }

    /**
     * Normaliza a chave Pix conforme o tipo:
     * CPF/CNPJ = só dígitos; e-mail = minúsculo; telefone = E.164 (+55);
     * aleatória = como informada.
     */
    private function normalizePixKey(string|PixKeyType|null $type, ?string $key): ?string
    {
        $key = $this->blankToNull($key);

        if ($key === null) {
            return null;
        }

        $typeValue = $type instanceof PixKeyType ? $type->value : (string) $type;

        return match ($typeValue) {
            PixKeyType::CPF->value, PixKeyType::CNPJ->value => $this->onlyDigits($key),
            PixKeyType::EMAIL->value => mb_strtolower(trim($key)),
            PixKeyType::PHONE->value => $this->normalizePhoneKey($key),
            default => trim($key),
        };
    }

    /** Telefone no formato do Pix: +55 + DDD + número. */
    private function normalizePhoneKey(string $key): string
    {
        $digits = $this->onlyDigits($key) ?? '';

        if (! str_starts_with($digits, '55') || strlen($digits) < 12) {
            $digits = '55'.$digits;
        }

        return '+'.$digits;
    }

    private function clearOtherDefaults(Teacher $teacher, int $keepId): void
    {
        $teacher->payoutAccounts()
            ->where('id', '!=', $keepId)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    private function hasDefaultBesides(Teacher $teacher, int $exceptId): bool
    {
        return $teacher->payoutAccounts()
            ->where('id', '!=', $exceptId)
            ->where('is_default', true)
            ->exists();
    }

    private function blankToNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function onlyDigits(?string $value): ?string
    {
        $value = $this->blankToNull($value);

        return $value === null ? null : preg_replace('/\D+/', '', $value);
    }
}
