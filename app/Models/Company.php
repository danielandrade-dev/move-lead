<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
final class Company extends Model
{
    use HasFactory;

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
        return $this->morphMany(Contract::class, 'contractable');
    }

    /**
     * Retorna as lojas ativas da empresa
     */
    public function activeStores()
    {
        return $this->stores()
            ->where('is_active', true)
            ->whereHas('contracts', function ($query): void {
                $query->active();
            });
    }

    /**
     * Verifica se a empresa tem contrato ativo
     */
    public function hasActiveContract(): bool
    {
        return $this->contracts()
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Retorna o contrato ativo atual
     */
    public function activeContract()
    {
        return $this->contracts()
            ->where('is_active', true)
            ->latest()
            ->first();
    }

    /**
     * Escopo para empresas com contrato ativo
     */
    public function scopeWithActiveContract($query)
    {
        return $query->whereHas('contracts', function ($query): void {
            $query->where('is_active', true);
        });
    }
}
