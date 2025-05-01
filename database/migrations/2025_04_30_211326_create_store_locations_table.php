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
        Schema::create('store_locations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Nome do ponto (Ex: "Filial Centro", "Ponto Shopping X")
            $table->text('address');
            $table->string('city');
            $table->string('state', 2);
            $table->string('zip_code', 10);
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 10, 8);
            $table->integer('coverage_radius')->default(10); // Raio em KM (mínimo 10)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Índice espacial para otimizar buscas por proximidade
            $table->spatialIndex(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_locations');
    }
};
