<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Planos do SaaS (catálogo da plataforma) + colunas de billing por gateway
 * na assinatura do treinador.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saas_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('max_students')->nullable(); // null = ilimitado
            $table->decimal('price_monthly', 8, 2)->default(0);
            $table->decimal('price_quarterly', 8, 2)->default(0);
            $table->decimal('price_annual', 8, 2)->default(0);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('teacher_id')
                ->constrained('saas_plans')->nullOnDelete();
            $table->string('period', 20)->nullable()->after('plan_id');
            $table->string('gateway', 20)->default('manual')->after('period'); // manual|mercadopago
            $table->string('preference_id')->nullable()->after('gateway');
            $table->string('payment_id')->nullable()->after('preference_id');
            $table->dropColumn('plan');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('plan')->default('basic');
            $table->dropConstrainedForeignId('plan_id');
            $table->dropColumn(['period', 'gateway', 'preference_id', 'payment_id']);
        });

        Schema::dropIfExists('saas_plans');
    }
};
