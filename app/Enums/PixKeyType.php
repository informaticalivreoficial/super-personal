<?php

namespace App\Enums;

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
}
