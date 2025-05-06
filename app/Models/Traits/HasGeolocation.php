<?php

declare(strict_types=1);

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Trait que fornece funcionalidades de geolocalização para modelos.
 *
 * Implementa métodos para trabalhar com dados geoespaciais usando PostGIS,
 * incluindo cálculo de distâncias, filtragem por raio e ordenação.
 * Requer uma coluna 'geom' do tipo geometry no banco de dados.
 */
trait HasGeolocation
{
    /**
     * Calcula a distância entre dois pontos em quilômetros
     *
     * Utiliza o PostGIS para calcular a distância entre a localização do modelo
     * atual e um ponto específico definido por latitude e longitude.
     *
     * @param float $latitude Latitude do ponto de destino
     * @param float $longitude Longitude do ponto de destino
     * @return float Distância em quilômetros
     */
    public function getDistanceTo(float $latitude, float $longitude): float
    {
        return DB::selectOne('
            SELECT ST_Distance(
                geom::geography,
                ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
            ) / 1000 as distance
            FROM ' . $this->getTable() . '
            WHERE id = ?
        ', [$longitude, $latitude, $this->id])->distance;
    }

    /**
     * Escopo para filtrar registros dentro de um raio em quilômetros
     *
     * Adiciona uma condição à query para retornar apenas registros
     * que estejam dentro do raio especificado a partir do ponto informado.
     *
     * @param Builder $query A query do Eloquent
     * @param float $latitude Latitude do ponto central
     * @param float $longitude Longitude do ponto central
     * @param float $radius Raio de busca em quilômetros
     * @return Builder A query modificada
     */
    public function scopeWithinRadius(Builder $query, float $latitude, float $longitude, float $radius): Builder
    {
        return $query->whereRaw('
            ST_Distance(
                geom::geography,
                ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
            ) / 1000 <= ?
        ', [$longitude, $latitude, $radius]);
    }

    /**
     * Escopo para ordenar por distância a partir de um ponto
     *
     * Adiciona uma ordenação à query para retornar registros
     * ordenados pela proximidade ao ponto especificado.
     *
     * @param Builder $query A query do Eloquent
     * @param float $latitude Latitude do ponto de referência
     * @param float $longitude Longitude do ponto de referência
     * @param string $direction Direção da ordenação ('asc' para mais próximos primeiro, 'desc' para mais distantes primeiro)
     * @return Builder A query modificada
     */
    public function scopeOrderByDistance(Builder $query, float $latitude, float $longitude, string $direction = 'asc'): Builder
    {
        return $query->orderByRaw(
            'ST_Distance(
                geom::geography,
                ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
            ) / 1000 ' . $direction,
            [$longitude, $latitude],
        );
    }

    /**
     * Retorna as coordenadas em formato array
     *
     * @return array<string, float> Array associativo com as chaves 'latitude' e 'longitude'
     */
    public function getCoordinates(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }

    /**
     * Método de inicialização do trait
     *
     * Executado automaticamente quando o trait é carregado.
     * Configura um evento para atualizar a coluna geom sempre que o modelo for salvo
     * e as coordenadas latitude/longitude estiverem definidas.
     *
     * @return void
     */
    protected static function bootHasGeolocation(): void
    {
        static::saving(function ($model): void {
            if (isset($model->latitude) && isset($model->longitude)) {
                $model->geom = DB::raw("ST_SetSRID(ST_MakePoint($model->longitude, $model->latitude), 4326)");
            }
        });
    }
}
