<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Bloqueia endpoints por papel (users.role): role:teacher, role:student, role:admin.
 */
class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role?->value, $roles, true)) {
            abort(403, 'Sem permissão para este recurso.');
        }

        return $next($request);
    }
}
