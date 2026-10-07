<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('bio')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('document', 30)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('profile_photo')->nullable();
            $table->string('cref', 30)->nullable();
            $table->string('specialty')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
