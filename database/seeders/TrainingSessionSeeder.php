<?php

namespace Database\Seeders;

use App\Enums\SessionItemType;
use App\Enums\TrainingSessionStatus;
use App\Models\Sport;
use App\Models\TrainingPlan;
use App\Models\TrainingSession;
use App\Models\TrainingSessionItem;
use Illuminate\Database\Seeder;

class TrainingSessionSeeder extends Seeder
{
    public function run(): void
    {
        $planos = TrainingPlan::with('weeks')->where('status', 'active')->get();

        foreach ($planos as $plano) {
            foreach ($plano->weeks as $semana) {
                if ($semana->sessions()->exists()) {
                    continue;
                }

                $this->criarSemana($plano->teacher_id, $plano->student_id, $semana->id, $semana->start_date);
            }
        }
    }

    private function criarSemana(int $teacherId, int $studentId, int $weekId, $startDate): void
    {
        $treinos = [
            [
                'offset' => 0, 'sport' => 'Corrida', 'title' => 'Intervalado 6x800m',
                'duration' => 3600, 'distance' => 12000, 'intensity' => 'Z4',
                'target_pace' => '5:10/km', 'target_heart_rate' => '165 bpm',
                'description' => 'Rodada de intervalados no ritmo de prova.',
                'instructions' => 'Aqueça 15 min leve. Execute 6 tiros de 800m com 400m de recuperação ativa. Volte à calma 10 min.',
                'items' => true,
            ],
            [
                'offset' => 2, 'sport' => 'Natação', 'title' => 'Natação técnica de braçada',
                'duration' => 2700, 'distance' => 2000, 'intensity' => 'Z3',
                'target_pace' => '1:50/100m',
                'description' => 'Drills de braçada e rodada de capacidade.',
                'instructions' => '200m quente, 6x50m drills, 8x100m ritmo forte, 100m desaquecimento.',
            ],
            [
                'offset' => 4, 'sport' => 'Ciclismo', 'title' => 'Rodagem Z2',
                'duration' => 5400, 'distance' => 45000, 'intensity' => 'Z2',
                'target_heart_rate' => '140 bpm', 'target_power' => '180W',
                'description' => 'Pedalada aeróbica de base.',
                'instructions' => 'Mantenha cadência 85-95 rpm e esforço constante.',
            ],
            [
                'offset' => 5, 'sport' => 'Triathlon', 'title' => 'Brick bike + corrida',
                'duration' => 5400, 'distance' => 50000, 'intensity' => 'Z3',
                'description' => 'Transição bike-corrida.',
                'instructions' => '90 min de bike em ritmo moderado seguidos de 20 min de corrida em ritmo de prova.',
            ],
        ];

        foreach ($treinos as $treino) {
            $sport = Sport::where('name', $treino['sport'])->first();

            $sessao = TrainingSession::create([
                'teacher_id' => $teacherId,
                'training_week_id' => $weekId,
                'student_id' => $studentId,
                'sport_id' => $sport?->id,
                'title' => $treino['title'],
                'description' => $treino['description'] ?? null,
                'scheduled_date' => $startDate->copy()->addDays($treino['offset'])->format('Y-m-d'),
                'estimated_duration' => $treino['duration'],
                'distance' => $treino['distance'] ?? null,
                'intensity' => $treino['intensity'] ?? null,
                'target_pace' => $treino['target_pace'] ?? null,
                'target_heart_rate' => $treino['target_heart_rate'] ?? null,
                'target_power' => $treino['target_power'] ?? null,
                'instructions' => $treino['instructions'] ?? null,
                'status' => TrainingSessionStatus::PLANNED,
                'sort_order' => $treino['offset'],
            ]);

            if (! empty($treino['items'])) {
                $this->criarItens($sessao);
            }
        }
    }

    private function criarItens(TrainingSession $sessao): void
    {
        $itens = [
            ['type' => SessionItemType::WARMUP, 'title' => 'Aquecimento', 'description' => '15 min de corrida leve + mobilidade.', 'duration' => 900, 'sort_order' => 1],
            ['type' => SessionItemType::WORK, 'title' => '6x800m no ritmo de prova', 'description' => '6 repetições de 800m a 5:10/km.', 'distance' => 800, 'repetitions' => 6, 'rest' => 120, 'target' => '5:10/km', 'intensity' => 'Z4', 'sort_order' => 2],
            ['type' => SessionItemType::RECOVERY, 'title' => 'Recuperação ativa entre tiros', 'description' => '400m de trote leve entre cada tiro.', 'distance' => 400, 'intensity' => 'Z1', 'sort_order' => 3],
            ['type' => SessionItemType::COOLDOWN, 'title' => 'Volta à calma', 'description' => '10 min leve + alongamento.', 'duration' => 600, 'sort_order' => 4],
        ];

        foreach ($itens as $item) {
            TrainingSessionItem::create(array_merge($item, [
                'training_session_id' => $sessao->id,
            ]));
        }
    }
}
