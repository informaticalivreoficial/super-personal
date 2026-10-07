<?php

namespace App\Enums;

enum SessionItemType: string
{
    case WARMUP = 'warmup';
    case WORK = 'work';
    case RECOVERY = 'recovery';
    case COOLDOWN = 'cooldown';
    case OTHER = 'other';

    public static function labels(): array
    {
        return [
            self::WARMUP->value => 'Aquecimento',
            self::WORK->value => 'Bloco principal',
            self::RECOVERY->value => 'Recuperação',
            self::COOLDOWN->value => 'Volta à calma',
            self::OTHER->value => 'Outro',
        ];
    }
}
