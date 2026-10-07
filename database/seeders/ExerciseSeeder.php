<?php

namespace Database\Seeders;

use App\Enums\ExerciseDifficulty;
use App\Models\Exercise;
use App\Models\Sport;
use Illuminate\Database\Seeder;

class ExerciseSeeder extends Seeder
{
    public function run(): void
    {
        $exercicios = [
            ['sport' => 'Corrida', 'name' => 'Corrida intervalada 6x800m', 'difficulty' => ExerciseDifficulty::HARD,
                'description' => 'Rodada de intervalados clássicos para desenvolver VO2 máx.',
                'instructions' => 'Aquecimento de 15 min leve. 6 tiros de 800m no ritmo de prova (5k), com 400m de recuperação ativa entre eles. Volta à calma de 10 min.'],
            ['sport' => 'Corrida', 'name' => 'Rodagem contínua', 'difficulty' => ExerciseDifficulty::EASY,
                'description' => 'Corrida em ritmo conversacional para base aeróbica.',
                'instructions' => 'Mantenha o ritmo confortável, capaz de conversar durante toda a sessão.'],
            ['sport' => 'Corrida', 'name' => 'Longão', 'difficulty' => ExerciseDifficulty::MEDIUM,
                'description' => 'Corrida longa de fim de semana para resistência.',
                'instructions' => 'Aumente a distância gradualmente. Hidrate-se e mantenha ritmo leve.'],
            ['sport' => 'Ciclismo', 'name' => 'Pedalada Z2', 'difficulty' => ExerciseDifficulty::EASY,
                'description' => 'Treino aeróbico em zona 2.',
                'instructions' => 'Cadência 85-95 rpm, força constante, FC na zona 2.'],
            ['sport' => 'Ciclismo', 'name' => 'Subidas em ritmo', 'difficulty' => ExerciseDifficulty::HARD,
                'description' => 'Blocos de subida em ritmo forte.',
                'instructions' => '3 blocos de 10 min em subida com esforço controlado.'],
            ['sport' => 'Natação', 'name' => 'Natação técnica de braçada', 'difficulty' => ExerciseDifficulty::MEDIUM,
                'description' => 'Drills para aperfeiçoar a braçada.',
                'instructions' => '200m quente. 6x50m drills de braçada com prancha entre cada. 100m desaquecimento.'],
            ['sport' => 'Natação', 'name' => 'Rodada puxada', 'difficulty' => ExerciseDifficulty::HARD,
                'description' => 'Séries de velocidade com nadador auxiliar.',
                'instructions' => '8x100m alternando 50m forte / 50m leve, descanso 30s.'],
            ['sport' => 'Musculação', 'name' => 'Agachamento livre', 'difficulty' => ExerciseDifficulty::MEDIUM,
                'description' => 'Exercício base de força para membros inferiores.',
                'instructions' => '4x10 repetições. Coluna neutra, joelhos alinhados aos pés, descida controlada.'],
            ['sport' => 'Musculação', 'name' => 'Prancha isométrica', 'difficulty' => ExerciseDifficulty::EASY,
                'description' => 'Fortalecimento do core.',
                'instructions' => '3 séries de 45-60s. Abdômen e glúteos contraídos, sem afundar a lombar.'],
            ['sport' => 'Mobilidade', 'name' => 'Mobilidade de quadril', 'difficulty' => ExerciseDifficulty::EASY,
                'description' => 'Rotina de mobilidade para quadril.',
                'instructions' => '5 movimentos, 30s cada lado. Sem dor, amplitude progressiva.'],
        ];

        foreach ($exercicios as $exercicio) {
            $sport = Sport::where('name', $exercicio['sport'])->first();

            Exercise::firstOrCreate(
                ['name' => $exercicio['name'], 'teacher_id' => null],
                [
                    'sport_id' => $sport?->id,
                    'difficulty' => $exercicio['difficulty'],
                    'description' => $exercicio['description'],
                    'instructions' => $exercicio['instructions'],
                    'active' => true,
                ]
            );
        }
    }
}
