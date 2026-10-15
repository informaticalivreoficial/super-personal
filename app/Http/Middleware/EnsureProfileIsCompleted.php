<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige o cadastro completo do TREINADOR antes de usar o painel.
 *
 * O CPF é obrigatório porque o parceiro de pagamentos recusa a cobrança sem a
 * identificação do pagador (`customer.tax_id`) — sem ele o treinador não
 * consegue assinar a plataforma (Pix/cartão); o celular é o contato do tenant.
 * Sem esses dados o treinador é levado para `/admin/completar-cadastro`.
 *
 * O admin da plataforma (sem tenant, sem cobrança própria) não é afetado, e a
 * própria página de completar cadastro fica liberada para não gerar loop.
 */
class EnsureProfileIsCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isTeacher()
            && $user->missingProfileFields() !== []
            && ! $request->routeIs('profile.complete')) {
            return redirect()->route('profile.complete');
        }

        return $next($request);
    }
}
