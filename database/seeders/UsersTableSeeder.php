<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        // Criar roles
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin',       'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'manager',     'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'employee',    'guard_name' => 'web']);

        // Criar ou recuperar super-admin
        $user = User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL')],
            [
                'name'               => env('ADMIN_NOME'),
                'email_verified_at'  => now(),
                'password'           => bcrypt(env('ADMIN_PASS')),
                'remember_token'     => \Illuminate\Support\Str::random(10),
                'status'             => 1,
            ]
        );

        if (!$user->hasRole('super-admin')) {
            $user->assignRole('super-admin');
        }

        // Usuários fake distribuídos por role
        User::factory()->count(5)->create()->each(fn ($u) => $u->assignRole('admin'));
        User::factory()->count(5)->create()->each(fn ($u) => $u->assignRole('manager'));
        User::factory()->count(20)->create()->each(fn ($u) => $u->assignRole('employee'));
    }
}
