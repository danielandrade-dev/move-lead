<?php

declare(strict_types=1);

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Trait que fornece atributos e métodos comuns para modelos.
 *
 * Adiciona funcionalidades para verificar status ativo/inativo,
 * escopos de consulta e formatação de datas.
 */
trait HasCommonAttributes
{
    /**
     * Escopo para filtrar registros ativos
     *
     * Adiciona um filtro à consulta para retornar apenas registros
     * onde o campo 'is_active' é verdadeiro.
     *
     * @param Builder $query A query do Eloquent
     * @return Builder A query modificada
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Escopo para filtrar registros inativos
     *
     * Adiciona um filtro à consulta para retornar apenas registros
     * onde o campo 'is_active' é falso.
     *
     * @param Builder $query A query do Eloquent
     * @return Builder A query modificada
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Verifica se o registro está ativo
     *
     * @return bool Verdadeiro se o registro estiver ativo, falso caso contrário
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * Retorna a data de criação formatada
     *
     * @param string $format Formato da data (padrão: 'd/m/Y H:i:s')
     * @return string Data formatada
     */
    public function getCreatedAtFormated(string $format = 'd/m/Y H:i:s'): string
    {
        return Carbon::parse($this->created_at)->format($format);
    }

    /**
     * Retorna a data de atualização formatada
     *
     * @param string $format Formato da data (padrão: 'd/m/Y H:i:s')
     * @return string Data formatada
     */
    public function getUpdatedAtFormated(string $format = 'd/m/Y H:i:s'): string
    {
        return Carbon::parse($this->updated_at)->format($format);
    }
}
