<?php

namespace Database\Seeders;

use App\Models\Sport;
use Illuminate\Database\Seeder;

class SportSeeder extends Seeder
{
    public function run(): void
    {
        $modalidades = [
            ['name' => 'Corrida', 'description' => 'Treinos de corrida de rua e pista.'],
            ['name' => 'Ciclismo', 'description' => 'Treinos em estrada, montanha ou indoor.'],
            ['name' => 'Natação', 'description' => 'Treinos e técnicas de natação.'],
            ['name' => 'Triathlon', 'description' => 'Treinos combinados de natação, ciclismo e corrida.'],
            ['name' => 'Musculação', 'description' => 'Treinos de força com pesos livres e máquinas.'],
            ['name' => 'Funcional', 'description' => 'Treinos funcionais e de condição física geral.'],
            ['name' => 'Mobilidade', 'description' => 'Alongamentos, mobilidade e prevenção de lesões.'],
            ['name' => 'Emagrecimento', 'description' => 'Treinos voltados para perda de peso.'],
        ];

        foreach ($modalidades as $modalidade) {
            Sport::firstOrCreate(['name' => $modalidade['name']], $modalidade);
        }
    }
}
