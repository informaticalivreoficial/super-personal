<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Paginação das listas da API com teto.
 *
 * `per_page` vem do cliente; sem limite, uma requisição pode pedir a base
 * inteira (custo de memória/DB e resposta gigante). Mantemos o padrão de 15 e
 * aceitamos no máximo 100 por página.
 */
class Pagination
{
    public const DEFAULT_PER_PAGE = 15;

    public const MAX_PER_PAGE = 100;

    public static function perPage(Request $request, int $default = self::DEFAULT_PER_PAGE, int $max = self::MAX_PER_PAGE): int
    {
        $requested = $request->integer('per_page', $default);

        return max(1, min($max, $requested > 0 ? $requested : $default));
    }
}
