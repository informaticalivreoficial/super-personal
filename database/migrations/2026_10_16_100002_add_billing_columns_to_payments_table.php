<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colunas de billing nas cobranças do aluno.
 *
 * `channel`: online (pago pelo app, cai no PagBank da plataforma) x manual
 * (registro — o treinador marca como paga por fora, sem gateway).
 * `fee_amount`/`net_amount`: preenchidos só no pagamento online (taxa da
 * plataforma e líquido do treinador).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('channel', 20)->default('manual')->after('amount');
            $table->decimal('fee_amount', 10, 2)->nullable()->after('amount');
            $table->decimal('net_amount', 10, 2)->nullable()->after('fee_amount');
            $table->string('gateway', 20)->nullable()->after('payment_method');
            $table->string('gateway_order_id')->nullable()->after('gateway');
            $table->string('gateway_status', 20)->nullable()->after('gateway_order_id');
            $table->foreignId('payout_id')->nullable()->after('gateway_status')
                ->constrained('payouts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payout_id');
            $table->dropColumn([
                'channel', 'fee_amount', 'net_amount',
                'gateway', 'gateway_order_id', 'gateway_status',
            ]);
        });
    }
};
