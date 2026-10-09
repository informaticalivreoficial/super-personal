<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Código de convite do aluno: o professor compartilha e o aluno cria a
 * própria conta de acesso (app Android) em POST /api/v1/auth/student-register.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('invite_code', 16)->nullable()->unique()->after('user_id');
        });

        // Backfill: alunos já criados sem conta de login ganham um código.
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

        DB::table('students')->whereNull('user_id')->orderBy('id')->pluck('id')->each(function ($id) use ($alphabet) {
            do {
                $code = '';
                for ($i = 0; $i < 8; $i++) {
                    $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
                }
            } while (DB::table('students')->where('invite_code', $code)->exists());

            DB::table('students')->where('id', $id)->update(['invite_code' => $code]);
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['invite_code']);
            $table->dropColumn('invite_code');
        });
    }
};
