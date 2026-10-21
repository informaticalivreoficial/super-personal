<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Preferências de lembretes automáticos por treinador.
 *
 * O comando `notifications:send-reminders` lê estas colunas para decidir se
 * envia os avisos ao aluno e com quantos dias de antecedência a cobrança
 * "a vencer" é avisada (antes era fixo em 3 dias para todos).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->boolean('reminders_enabled')->default(true)->after('views');
            $table->unsignedTinyInteger('reminder_due_days')->default(3)->after('reminders_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn(['reminders_enabled', 'reminder_due_days']);
        });
    }
};
