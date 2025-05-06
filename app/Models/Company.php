<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Modelo de Empresa
 *
 * Representa as empresas que podem ter múltiplas lojas no sistema.
 * Gerencia informações corporativas e possui relacionamentos com lojas e contratos.
 */
final class Company extends Model
{
    use HasFactory;

    /**
     * Atributos que são permitidos para atribuição em massa
     *
     * @var array<int, string>
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
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Define o relacionamento com as lojas da empresa
     *
     * Uma empresa pode ter múltiplas lojas associadas
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany Relacionamento com as lojas
     */
    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    /**
     * Define o relacionamento com os contratos da empresa
     *
     * Uma empresa pode ter múltiplos contratos (ativos ou inativos)
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany Relacionamento morfológico com os contratos
     */
    public function contracts()
    {
        return $this->morphMany(Contract::class, 'contractable');
    }

    /**
     * Retorna as lojas ativas da empresa que possuem contratos ativos
     *
     * Filtra apenas lojas que estão ativas e possuem ao menos um contrato ativo
     *
     * @return \Illuminate\Database\Eloquent\Builder Query com as lojas ativas
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
     *
     * @return bool Verdadeiro se a empresa tiver pelo menos um contrato ativo
     */
    public function hasActiveContract(): bool
    {
        return $this->contracts()
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Retorna o contrato ativo mais recente da empresa
     *
     * Utiliza o relacionamento de contratos filtrando apenas os ativos
     * e ordenando pelo mais recente
     *
     * @return Contract|null O contrato ativo mais recente ou null se não existir
     */
    public function activeContract()
    {
        return $this->contracts()
            ->where('is_active', true)
            ->latest()
            ->first();
    }

    /**
     * Escopo para filtrar empresas com contrato ativo
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Query do Eloquent
     * @return \Illuminate\Database\Eloquent\Builder Query modificada
     */
    public function scopeWithActiveContract($query)
    {
        return $query->whereHas('contracts', function ($query): void {
            $query->where('is_active', true);
        });
    }
}
