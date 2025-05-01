/**
 * Migration para Criação da Tabela de Lojas
 *
 * Este arquivo faz parte do projeto Move Lead.
 *
 * @package     MoveLead
 * @subpackage  Database
 * @category    Migration
 * @version     1.0.0
 * @author      Daniel Moreira de Andrade
 * @link        https://github.com/danielmoraes/move-lead
 * @copyright   Copyright (c) 2025 AndradeTecnologia (http://www.andradetecnologia.com.br)
 * @license     Proprietário
 *
 * Criado em:   30/04/2025
 * Modificado:  30/04/2025
 */

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
        Schema::create('stores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->string('name');
            $table->string('document')->unique(); // CNPJ
            $table->string('email');
            $table->string('phone');
            $table->text('address');
            $table->string('city');
            $table->string('state', 2);
            $table->string('zip_code', 10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
