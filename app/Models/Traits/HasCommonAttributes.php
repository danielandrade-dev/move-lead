<?php

declare(strict_types=1);

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

trait HasCommonAttributes
{
    /**
     * Escopo para filtrar registros ativos
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Escopo para filtrar registros inativos
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Verifica se o registro está ativo
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * Retorna a data de criação formatada
     */
    public function getCreatedAtFormated(string $format = 'd/m/Y H:i:s'): string
    {
        return Carbon::parse($this->created_at)->format($format);
    }

    /**
     * Retorna a data de atualização formatada
     */
    public function getUpdatedAtFormated(string $format = 'd/m/Y H:i:s'): string
    {
        return Carbon::parse($this->updated_at)->format($format);
    }
}
