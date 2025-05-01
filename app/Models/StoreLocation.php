<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\HasGeolocation;

final class StoreLocation extends BaseModel
{
    use HasGeolocation;

    /**
     * Atributos que são permitidos para atribuição em massa
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
     * Relacionamento com a loja
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Verifica se o endereço está dentro do raio de cobertura
     */
    public function isAddressInCoverage(float $latitude, float $longitude): bool
    {
        return $this->getDistanceTo($latitude, $longitude) <= $this->coverage_radius;
    }

    /**
     * Boot function from Laravel
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
