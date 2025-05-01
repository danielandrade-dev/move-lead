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
        Schema::create('lead_stores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')->constrained();
            $table->foreignId('store_id')->constrained();
            $table->foreignId('contract_id')->constrained();
            $table->enum('status', ['sent', 'viewed', 'contacted', 'converted', 'returned']);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_stores');
    }
};
