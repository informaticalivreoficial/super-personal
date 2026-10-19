<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prazo (dias) que o líquido de uma cobrança online fica "a liberar" antes de
 * entrar no saldo sacável do treinador. Default 7 (editável em Configurações).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('config', function (Blueprint $table) {
            $table->unsignedInteger('payout_hold_days')->default(7)->after('fee_percent');
        });
    }

    public function down(): void
    {
        Schema::table('config', function (Blueprint $table) {
            $table->dropColumn('payout_hold_days');
        });
    }
};
