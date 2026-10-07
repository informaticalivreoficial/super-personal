<?php

namespace Database\Seeders;

use App\Models\TrainingPlan;
use App\Models\TrainingWeek;
use Illuminate\Database\Seeder;

class TrainingWeekSeeder extends Seeder
{
    public function run(): void
    {
        $planos = TrainingPlan::where('status', 'active')->get();

        foreach ($planos as $plano) {
            if ($plano->weeks()->exists()) {
                continue;
            }

            $semanas = min(4, $plano->end_date ? $plano->start_date->diffInWeeks($plano->end_date) + 1 : 4);

            for ($n = 1; $n <= max(1, $semanas); $n++) {
                TrainingWeek::create([
                    'teacher_id' => $plano->teacher_id,
                    'training_plan_id' => $plano->id,
                    'week_number' => $n,
                    'name' => "Semana {$n}",
                    'start_date' => $plano->start_date->copy()->addWeeks($n - 1)->format('Y-m-d'),
                    'end_date' => $plano->start_date->copy()->addWeeks($n - 1)->addDays(6)->format('Y-m-d'),
                    'objective' => $n === 1 ? 'Adaptação e estabelecimento de rotina' : 'Progressão de volume',
                    'status' => 'pending',
                ]);
            }
        }
    }
}
