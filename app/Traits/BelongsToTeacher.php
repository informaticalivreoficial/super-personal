<?php

namespace App\Traits;

use App\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Isolamento multi-tenant (tenant = treinador).
 *
 * Aplica automaticamente "WHERE teacher_id = <treinador autenticado>"
 * em toda query do modelo, impedindo que um treinador acesse dados
 * de outro mesmo que uma Policy seja esquecida em algum endpoint.
 *
 * Regras:
 * - Convidado (sem autenticação): sem filtro (seeders/console).
 * - Administrador da plataforma: sem filtro (acesso global).
 * - Treinador: filtra pelo próprio tenant.
 * - Aluno: filtra pelo treinador ao qual pertence.
 * - Perfil não resolvido: filtra por id impossível (0) → nada visível.
 */
trait BelongsToTeacher
{
    public static function bootBelongsToTeacher(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (! auth()->check()) {
                return;
            }

            $teacherId = auth()->user()->resolveTenantId();

            if ($teacherId === null) {
                return;
            }

            $builder->where($builder->getModel()->getTable().'.teacher_id', $teacherId);
        });
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
