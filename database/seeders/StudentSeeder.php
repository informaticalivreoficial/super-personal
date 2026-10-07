<?php

namespace Database\Seeders;

use App\Enums\StudentGender;
use App\Enums\StudentLevel;
use App\Enums\UserRole;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = Teacher::whereHas('user', fn ($q) => $q->where('email', 'joao@superpersonal.test'))->first();

        if (! $teacher) {
            return;
        }

        $alunos = [
            [
                'user' => ['email' => 'marcos@superpersonal.test', 'name' => 'Marcos Oliveira'],
                'student' => [
                    'name' => 'Marcos Oliveira',
                    'phone' => '(11) 98888-1234',
                    'birth_date' => '1990-05-15',
                    'gender' => StudentGender::MALE,
                    'height' => 178,
                    'initial_weight' => 84.5,
                    'current_weight' => 79.2,
                    'target_weight' => 74.0,
                    'goal' => 'Preparação para Triathlon Sprint',
                    'fitness_level' => StudentLevel::INTERMEDIATE,
                    'training_experience' => '1 a 3 anos',
                    'available_days' => [1, 3, 5, 6],
                    'observations' => 'Histórico de lesão no joelho direito. Reforçar mobilidade.',
                    'started_at' => now()->subMonths(3)->format('Y-m-d'),
                ],
            ],
            [
                'user' => ['email' => 'ana@superpersonal.test', 'name' => 'Ana Souza'],
                'student' => [
                    'name' => 'Ana Souza',
                    'phone' => '(11) 97777-5678',
                    'birth_date' => '1995-09-22',
                    'gender' => StudentGender::FEMALE,
                    'height' => 165,
                    'initial_weight' => 72.0,
                    'current_weight' => 68.4,
                    'target_weight' => 62.0,
                    'goal' => 'Emagrecimento e condicionamento',
                    'fitness_level' => StudentLevel::BEGINNER,
                    'training_experience' => 'Nenhuma',
                    'available_days' => [2, 4, 6],
                    'started_at' => now()->subMonth()->format('Y-m-d'),
                ],
            ],
            [
                'user' => ['email' => 'pedro@superpersonal.test', 'name' => 'Pedro Santos'],
                'student' => [
                    'name' => 'Pedro Santos',
                    'phone' => '(11) 96666-9012',
                    'birth_date' => '1987-01-30',
                    'gender' => StudentGender::MALE,
                    'height' => 182,
                    'initial_weight' => 88.0,
                    'current_weight' => 85.0,
                    'target_weight' => 80.0,
                    'goal' => 'Preparação para meia maratona',
                    'fitness_level' => StudentLevel::ADVANCED,
                    'training_experience' => 'Mais de 3 anos',
                    'available_days' => [1, 2, 4, 6, 7],
                    'started_at' => now()->subMonths(6)->format('Y-m-d'),
                ],
            ],
        ];

        foreach ($alunos as $dados) {
            $user = User::firstOrCreate(
                ['email' => $dados['user']['email']],
                [
                    'name' => $dados['user']['name'],
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                    'remember_token' => Str::random(10),
                    'status' => 1,
                ]
            );

            $user->forceFill(['role' => UserRole::STUDENT])->save();

            Student::firstOrCreate(
                ['email' => $dados['student']['email'] ?? $dados['user']['email'], 'teacher_id' => $teacher->id],
                array_merge($dados['student'], [
                    'email' => $dados['user']['email'],
                    'user_id' => $user->id,
                    'active' => true,
                ])
            );
        }
    }
}
