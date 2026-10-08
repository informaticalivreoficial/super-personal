<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * O default original da coluna `type` era 'main', que NÃO existe no enum
 * SessionItemType (warmup|work|recovery|cooldown|other) — um insert sem `type`
 * gravaria um valor inválido e o cast enum quebraria na leitura.
 *
 * Corrige o default para 'work' (Bloco principal). A alteração usa SQL puro
 * porque o Laravel 10 sem doctrine/dbal não altera colunas em SQLite (testes);
 * lá o default nunca é aplicado pois a aplicação sempre grava `type`.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE training_session_items ALTER COLUMN type SET DEFAULT 'work'");
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE training_session_items ALTER COLUMN type SET DEFAULT 'main'");
        }
    }
};
