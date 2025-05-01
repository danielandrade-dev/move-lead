<?php

declare(strict_types=1);

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait HasGeolocation
{
    /**
     * Calcula a distância entre dois pontos em quilômetros
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
     */
    public function getCoordinates(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }

    /**
     * Boot do trait
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
