<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Checkout transparente do PagBank: o pedido gerado pela API já é
 * identificado por `subscriptions.payment_id` — a coluna `preference_id`
 * (id da preferência do gateway anterior) ficou sem uso.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('preference_id');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('preference_id')->nullable()->after('gateway');
        });
    }
};
