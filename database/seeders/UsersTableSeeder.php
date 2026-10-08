<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Usuários legados do starter. Papéis pela coluna `users.role`
 * (o spatie/laravel-permission foi removido — ver AGENTS.md).
 * Distribuição espelha o backfill da migration `add_role_to_users_table`:
 * admin/manager → admin; employee → teacher.
 */
class UsersTableSeeder extends Seeder
{
    public function run()
    {
        // Criar ou recuperar o admin da plataforma
        $user = User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL')],
            [
                'name' => env('ADMIN_NOME'),
                'email_verified_at' => now(),
                'password' => bcrypt(env('ADMIN_PASS')),
                'remember_token' => Str::random(10),
                'status' => 1,
            ]
        );

        $user->role = 'admin';
        $user->save();

        // Usuários fake distribuídos por papel
        User::factory()->count(10)->create(['role' => 'admin']);
        User::factory()->count(20)->create(['role' => 'teacher']);
    }
}
