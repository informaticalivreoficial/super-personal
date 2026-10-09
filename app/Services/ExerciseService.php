<?php

namespace App\Services;

use App\Models\Exercise;
use App\Models\User;

class ExerciseService
{
    /**
     * `teacher_id` é definido aqui (nunca via request): null = catálogo global
     * (admin da plataforma), >0 = biblioteca do treinador.
     *
     * @param  array<string, mixed>  $data
     */
    public function store(User $user, array $data): Exercise
    {
        $exercise = new Exercise;
        $exercise->teacher_id = $user->resolveTenantId();
        $exercise->fill($data);
        $exercise->save();

        return $exercise;
    }

    /**
     * Não altera `teacher_id`: a posse do exercício é preservada na edição.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Exercise $exercise, array $data): Exercise
    {
        $exercise->fill($data);
        $exercise->save();

        return $exercise;
    }

    public function delete(Exercise $exercise): void
    {
        $exercise->delete();
    }
}
