/**
 * Migration para Criação da Tabela de Leads
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

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('segment_id')->constrained('segments');
            $table->string('name' ,255);
            $table->string('email' ,255);
            $table->string('phone' ,20);
            $table->string('zip_code' ,10);
            $table->string('city' ,100);
            $table->string('state' ,2);
            $table->text('address');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 10, 8);
            $table->string('external_id' ,255)->nullable();
            $table->string('external_source' ,255)->nullable();
            $table->enum('status' ,['new' ,'pending' ,'approved' ,'rejected','archived','sent'])->default('new');
            $table->timestamps();
            $table->softDeletes();
            $table->spatialIndex(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
