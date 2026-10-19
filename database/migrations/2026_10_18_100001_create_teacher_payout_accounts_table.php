<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contas/chaves de recebimento do treinador (destino dos saques/repasse).
 *
 * Antes existia só a coluna `teachers.pix_key`; agora o treinador pode ter
 * várias (chave Pix ou conta bancária) e uma delas é a padrão. O tenant vem
 * sempre do Service (`teacher_id` fora do fillable).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_payout_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();

            // PayoutAccountType: pix | bank_account
            $table->string('type', 20);
            $table->string('label', 60)->nullable();

            // Chave Pix
            $table->string('pix_key')->nullable();
            $table->string('pix_key_type', 20)->nullable();

            // Conta bancária
            $table->string('bank_name')->nullable();
            $table->string('bank_code', 10)->nullable();
            $table->string('agency', 20)->nullable();
            $table->string('account', 30)->nullable();
            $table->string('account_digit', 5)->nullable();
            $table->string('account_type', 20)->nullable(); // BankAccountType
            $table->string('holder_name')->nullable();
            $table->string('holder_document', 20)->nullable();

            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['teacher_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_payout_accounts');
    }
};
