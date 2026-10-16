<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Taxa da plataforma sobre as cobranças online do aluno (%).
 * Editável em Configurações; default 5%.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('config', function (Blueprint $table) {
            $table->decimal('fee_percent', 5, 2)->default(5.00)->after('analytics_id');
        });
    }

    public function down(): void
    {
        Schema::table('config', function (Blueprint $table) {
            $table->dropColumn('fee_percent');
        });
    }
};
