<?php

namespace Database\Seeders;

use App\Enums\TrainingPlanStatus;
use App\Models\Student;
use App\Models\TrainingPlan;
use Illuminate\Database\Seeder;

class TrainingPlanSeeder extends Seeder
{
    public function run(): void
    {
        $planos = [
            [
                'student_email' => 'marcos@superpersonal.test',
                'name' => 'Preparação Triathlon Sprint',
                'description' => 'Ciclo de 12 semanas para estreia no triathlon sprint, com progressão de volume em natação, ciclismo e corrida.',
                'goal' => 'Concluir o Triathlon Sprint em ritmo de prova',
                'weeks' => 12,
            ],
            [
                'student_email' => 'ana@superpersonal.test',
                'name' => 'Emagrecimento 12 semanas',
                'description' => 'Programa de emagrecimento com treinos funcionais, corrida leve e orientação alimentar.',
                'goal' => 'Perder 8 kg com saúde e ganhar condicionamento',
                'weeks' => 12,
            ],
            [
                'student_email' => 'pedro@superpersonal.test',
                'name' => 'Preparação Meia Maratona',
                'description' => 'Base e pico de treinamento para meia maratona de 21 km.',
                'goal' => 'Bater a marca pessoal de 21 km',
                'weeks' => 10,
            ],
        ];

        foreach ($planos as $plano) {
            $student = Student::where('email', $plano['student_email'])->first();

            if (! $student) {
                continue;
            }

            TrainingPlan::firstOrCreate(
                ['name' => $plano['name'], 'student_id' => $student->id],
                [
                    'teacher_id' => $student->teacher_id,
                    'description' => $plano['description'],
                    'goal' => $plano['goal'],
                    'start_date' => now()->subWeeks(2)->format('Y-m-d'),
                    'end_date' => now()->addWeeks($plano['weeks'] - 2)->format('Y-m-d'),
                    'status' => TrainingPlanStatus::ACTIVE,
                ]
            );
        }
    }
}
