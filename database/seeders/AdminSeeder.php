<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@superpersonal.test');

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NOME', 'Administrador'),
                'password' => bcrypt(env('ADMIN_PASS', 'password')),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
                'status' => 1,
            ]
        );

        $user->forceFill(['role' => UserRole::ADMIN])->save();
    }
}
