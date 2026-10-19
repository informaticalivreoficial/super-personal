<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Move a chave Pix que ficava em `teachers` para a nova tabela de contas de
 * recebimento (uma conta Pix padrão por treinador que já tinha chave).
 *
 * Depois remove as colunas antigas: a chave agora é gerida na área Financeiro.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('teachers')
            ->whereNotNull('pix_key')
            ->where('pix_key', '!=', '')
            ->orderBy('id')
            ->get(['id', 'pix_key', 'pix_key_type'])
            ->each(function ($teacher) {
                DB::table('teacher_payout_accounts')->insert([
                    'teacher_id' => $teacher->id,
                    'type' => 'pix',
                    'pix_key' => $teacher->pix_key,
                    'pix_key_type' => $teacher->pix_key_type,
                    'is_default' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn(['pix_key', 'pix_key_type']);
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('pix_key')->nullable()->after('document');
            $table->string('pix_key_type', 20)->nullable()->after('pix_key');
        });
    }
};
