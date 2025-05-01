<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
            $table->integer('leads_contracted');
            $table->integer('leads_delivered')->default(0);
            $table->integer('leads_returned')->default(0);
            $table->integer('leads_warranty_used')->default(0);
            $table->integer('warranty_percentage')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('auto_close_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Garantir que uma entidade (Company ou Store) só pode ter um contrato ativo por vez
            $table->index(['contractable_id', 'contractable_type', 'is_active']);
        });

        // Adicionando restrição via SQL para garantir apenas um contrato ativo por entidade
        DB::statement('
            CREATE UNIQUE INDEX unique_active_contract
            ON contracts (contractable_id, contractable_type)
            WHERE is_active = true
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
