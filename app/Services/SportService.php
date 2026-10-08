<?php

namespace App\Services;

use App\Models\Sport;
use Illuminate\Validation\ValidationException;

class SportService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): Sport
    {
        $sport = new Sport;
        $sport->fill($data);
        $sport->save();

        return $sport;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Sport $sport, array $data): Sport
    {
        $sport->fill($data);
        $sport->save();

        return $sport;
    }

    /**
     * Exclusão bloqueada enquanto houver exercícios ou sessões vinculados
     * (FKs com restrictOnDelete — o erro viraria um 500).
     */
    public function delete(Sport $sport): void
    {
        if ($sport->exercises()->exists() || $sport->trainingSessions()->exists()) {
            throw ValidationException::withMessages([
                'sport' => 'Não é possível excluir: existem exercícios ou sessões vinculados a esta modalidade.',
            ]);
        }

        $sport->delete();
    }
}
