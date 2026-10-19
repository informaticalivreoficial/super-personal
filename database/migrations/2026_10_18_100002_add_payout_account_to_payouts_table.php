<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Destino do repasse congelado no próprio Payout.
 *
 * `payout_account_id` aponta para a conta escolhida (pode ser apagada depois —
 * `nullOnDelete`); `destination` guarda o texto do destino no momento do
 * pedido, para o histórico continuar legível mesmo se a conta mudar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->foreignId('payout_account_id')->nullable()->after('net_amount')
                ->constrained('teacher_payout_accounts')->nullOnDelete();
            $table->string('destination')->nullable()->after('payout_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payout_account_id');
            $table->dropColumn('destination');
        });
    }
};
