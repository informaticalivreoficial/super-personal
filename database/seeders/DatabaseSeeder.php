<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ConfigTableSeeder::class,
            // SaaS — base de dados do SportPlan
            AdminSeeder::class,
            TeacherSeeder::class,
            SportSeeder::class,
            ExerciseSeeder::class,
            StudentSeeder::class,
            TrainingPlanSeeder::class,
            TrainingWeekSeeder::class,
            TrainingSessionSeeder::class,
            PaymentSeeder::class,
        ]);
    }
}
