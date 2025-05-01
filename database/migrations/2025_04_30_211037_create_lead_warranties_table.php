/**
 * Migration para Criação da Tabela de Garantias de Leads
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
        Schema::create('lead_warranties', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_store_id')->constrained();
            $table->foreignId('new_lead_id')->nullable()->constrained('leads');
            $table->enum('status', ['pending', 'approved', 'rejected', 'waiting_replacement', 'replaced']);
            $table->text('return_reason');
            $table->text('analysis_notes')->nullable();
            $table->foreignId('analyzed_by')->nullable()->constrained('users');
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamp('replaced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_warranties');
    }
};
