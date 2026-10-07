<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Student;

/**
 * Base dos endpoints da API v1.
 */
abstract class ApiController extends Controller
{
    /**
     * Perfil de aluno do usuário autenticado.
     */
    protected function studentFromAuth(): Student
    {
        $student = auth()->user()->student;

        abort_if(! $student, 403, 'Perfil de aluno não encontrado.');

        return $student;
    }

    /**
     * Tenant (professor) do usuário autenticado.
     */
    protected function currentTeacherId(): int
    {
        $teacherId = auth()->user()->resolveTenantId();

        abort_if(! $teacherId, 403, 'Perfil de professor não encontrado.');

        return (int) $teacherId;
    }
}
