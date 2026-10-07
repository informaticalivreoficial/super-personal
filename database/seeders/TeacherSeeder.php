<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'joao@superpersonal.test'],
            [
                'name' => 'João Silva',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
                'status' => 1,
            ]
        );

        $user->forceFill(['role' => UserRole::TEACHER])->save();

        Teacher::firstOrCreate(
            ['user_id' => $user->id],
            [
                'name' => 'João Silva',
                'bio' => 'Treinador de triathlon, corrida, natação e ciclismo. Mais de 10 anos preparando atletas para provas de rua e triathlon.',
                'phone' => '(11) 99999-1234',
                'document' => '123.456.789-00',
                'cref' => 'CREF-123456-G',
                'specialty' => 'Triathlon, Corrida, Natação e Ciclismo',
                'active' => true,
            ]
        );
    }
}
