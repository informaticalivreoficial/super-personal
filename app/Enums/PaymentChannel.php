<?php

namespace App\Enums;

/**
 * Canal da cobrança do aluno.
 *
 * - online: paga pelo app, cai no PagBank da plataforma (gera taxa).
 * - manual: registro de cobrança paga por fora (sem gateway, sem taxa).
 */
enum PaymentChannel: string
{
    case MANUAL = 'manual';
    case ONLINE = 'online';

    public static function labels(): array
    {
        return [
            self::MANUAL->value => 'Manual',
            self::ONLINE->value => 'Online (pelo app)',
        ];
    }
}
