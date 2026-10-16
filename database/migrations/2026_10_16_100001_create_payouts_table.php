<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repasses da plataforma ao treinador.
 *
 * As cobranças online do aluno caem no PagBank da plataforma; a plataforma
 * retém a taxa (`config.fee_percent`) e repassa o líquido ao treinador. Cada
 * repasse agrupa um conjunto de cobranças (`payments.payout_id`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);                 // soma dos valores brutos
            $table->decimal('fee_total', 10, 2)->default(0);  // soma das taxas retidas
            $table->decimal('net_amount', 10, 2);             // valor a transferir ao treinador
            $table->string('status', 20)->default('requested'); // PayoutStatus
            $table->string('gateway', 20)->nullable();         // pagbank (transferência — futuro)
            $table->string('gateway_transfer_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['teacher_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
