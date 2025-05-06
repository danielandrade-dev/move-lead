<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\HasGeolocation;

/**
 * Modelo de Localização de Loja
 *
 * Representa os pontos de captação de uma loja.
 * Cada loja pode ter múltiplas localizações com diferentes raios de cobertura.
 * Possui funcionalidades para geolocalização e verificação de cobertura.
 */
final class StoreLocation extends BaseModel
{
    use HasGeolocation;

    /**
     * Atributos que são permitidos para atribuição em massa
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'store_id',
        'name',
        'zip_code',
        'city',
        'state',
        'address',
        'latitude',
        'longitude',
        'coverage_radius',
        'is_main',
        'is_active',
    ];

    /**
     * Atributos que devem ser convertidos para tipos nativos
     *
     * @var array<string, string>
     */
    protected $casts = [
        'store_id' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'coverage_radius' => 'integer',
        'is_main' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Define o relacionamento com a loja
     *
     * Uma localização pertence a uma única loja
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relacionamento com a loja
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Verifica se um determinado ponto está dentro do raio de cobertura
     *
     * Utiliza o método getDistanceTo do trait HasGeolocation para calcular
     * a distância entre dois pontos e compara com o raio de cobertura
     *
     * @param float $latitude Latitude do ponto a ser verificado
     * @param float $longitude Longitude do ponto a ser verificado
     * @return bool Verdadeiro se o ponto estiver dentro do raio de cobertura
     */
    public function isAddressInCoverage(float $latitude, float $longitude): bool
    {
        return $this->getDistanceTo($latitude, $longitude) <= $this->coverage_radius;
    }

    /**
     * Método de inicialização do modelo
     *
     * Configura eventos para garantir que só existe uma localização principal por loja
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        // Garante que só existe uma localização principal por loja
        static::saving(function ($location): void {
            if ($location->is_main) {
                static::where('store_id', $location->store_id)
                    ->where('id', '!=', $location->id)
                    ->update(['is_main' => false]);
            }
        });
    }
}
