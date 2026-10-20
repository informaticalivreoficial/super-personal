<?php

namespace App\Enums;

use App\Support\Document;

/**
 * Tipo da chave Pix usada para receber os repasses.
 */
enum PixKeyType: string
{
    case CPF = 'cpf';
    case CNPJ = 'cnpj';
    case EMAIL = 'email';
    case PHONE = 'phone';
    case RANDOM = 'random';

    public static function labels(): array
    {
        return [
            self::CPF->value => 'CPF',
            self::CNPJ->value => 'CNPJ',
            self::EMAIL->value => 'E-mail',
            self::PHONE->value => 'Telefone',
            self::RANDOM->value => 'Chave aleatória',
        ];
    }

    /**
     * Formata a chave para exibição/edição (CPF, CNPJ e telefone). O valor
     * guardado continua normalizado (só dígitos / E.164) — a formatação é só
     * de apresentação, no prefill do formulário e nas listas.
     */
    public function format(?string $key): ?string
    {
        if ($key === null || trim($key) === '') {
            return $key;
        }

        return match ($this) {
            self::CPF, self::CNPJ => Document::format($key) ?? $key,
            self::PHONE => $this->formatPhone($key),
            default => $key,
        };
    }

    /** Telefone do Pix guardado em E.164 (+55 DDD número) → "(DDD) número". */
    private function formatPhone(string $key): string
    {
        $digits = preg_replace('/\D+/', '', $key) ?? '';

        if ((strlen($digits) === 12 || strlen($digits) === 13) && str_starts_with($digits, '55')) {
            $digits = substr($digits, 2);
        }

        return match (strlen($digits)) {
            11 => preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $digits),
            10 => preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $digits),
            default => $key,
        };
    }
}
