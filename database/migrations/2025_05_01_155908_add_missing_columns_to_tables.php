<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Adicionar colunas faltantes na tabela companies
        Schema::table('companies', function (Blueprint $table): void {
            $table->string('zip_code', 10)->after('phone');
            $table->string('city')->after('zip_code');
            $table->string('state', 2)->after('city');
        });

        // Adicionar colunas faltantes na tabela users
        Schema::table('users', function (Blueprint $table): void {
            $table->string('type')->after('email');
            $table->foreignId('store_id')->nullable()->after('type')->constrained();
            $table->foreignId('company_id')->nullable()->after('store_id')->constrained();
            $table->boolean('is_active')->default(true)->after('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropColumn(['zip_code', 'city', 'state']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['type', 'store_id', 'company_id', 'is_active']);
        });
    }
};
