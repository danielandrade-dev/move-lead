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
        Schema::create('contracts', function (Blueprint $table): void {
            $table->id();
            $table->morphs('contractable'); // Para Company ou Store
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('lead_price', 10, 2);
            $table->integer('leads_per_month')->default(0);
            $table->boolean('is_active')->default(false);
            $table->text('terms');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['contractable_id', 'contractable_type', 'is_active'], 'unique_active_store_contract');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
