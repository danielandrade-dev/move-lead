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
        Schema::create('segment_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('segment_id')->constrained('segments');
            $table->string('field_name', 255);
            $table->string('field_type', 50);
            $table->boolean('is_required')->default(false);
            $table->text('field_options')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('segment_fields');
    }
};
