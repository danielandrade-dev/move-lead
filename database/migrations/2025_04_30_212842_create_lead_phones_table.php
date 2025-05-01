<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lead_phones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')->constrained()->onDelete('cascade');
            $table->string('phone_normalized'); // Telefone normalizado (apenas números)
            $table->string('phone_original'); // Telefone original
            $table->timestamps();

            // Índice para busca rápida
            $table->index('phone_normalized');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_phones');
    }
};
