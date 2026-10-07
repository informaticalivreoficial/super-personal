<?php

namespace App\Enums;

enum PostType: string
{
    case ARTIGO = 'artigo';
    case NOTICIA = 'noticia';
    case PAGINA = 'pagina';

    public static function labels(): array
    {
        return [
            self::ARTIGO->value => 'Artigo',
            self::NOTICIA->value => 'Notícia',
            self::PAGINA->value => 'Página',
        ];
    }
}