<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Company extends Model
{
    /**
     * Atributos que são permitidos para atribuição em massa
     */
    protected $fillable = [
        'name',
        'document',
        'email',
        'phone',
        'zip_code',
        'city',
        'state',
        'address',
        'is_active',
    ];

    /**
     * Atributos que devem ser convertidos para tipos nativos
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relacionamento com as lojas
     */
    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    /**
     * Relacionamento com os contratos
     */
    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * Retorna as lojas ativas da empresa
     */
    public function activeStores()
    {
        return $this->stores()
            ->active()
            ->withActiveContract();
    }

    /**
     * Verifica se a empresa tem contrato ativo
     */
    public function hasActiveContract(): bool
    {
        return $this->contracts()
            ->active()
            ->exists();
    }

    /**
     * Retorna o contrato ativo atual
     */
    public function activeContract()
    {
        return $this->contracts()
            ->active()
            ->latest()
            ->first();
    }

    /**
     * Escopo para empresas com contrato ativo
     */
    public function scopeWithActiveContract($query)
    {
        return $query->whereHas('contracts', function ($query) {
            $query->active();
        });
    }
}
