<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\HasGeolocation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Store extends Model
{
    use HasFactory;
    use HasGeolocation;
    use SoftDeletes;

    /**
     * Atributos que são permitidos para atribuição em massa
     */
    protected $fillable = [
        'company_id',
        'name',
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
        'company_id' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function locations()
    {
        return $this->hasMany(StoreLocation::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function activeContract()
    {
        return $this->contracts()
            ->active()
            ->latest()
            ->first();
    }

    // Método para encontrar leads elegíveis considerando todos os pontos de captação
    public function findEligibleLeads()
    {
        $locationIds = $this->locations()
            ->where('is_active', true)
            ->pluck('id');

        if ($locationIds->isEmpty()) {
            return collect();
        }

        return Lead::select('leads.*')
            ->selectRaw('
                MIN(
                    ST_Distance_Sphere(
                        point(leads.longitude, leads.latitude),
                        point(store_locations.longitude, store_locations.latitude)
                    ) * 0.001
                ) as min_distance_in_km
            ')
            ->join('store_locations', function ($join) use ($locationIds): void {
                $join->whereIn('store_locations.id', $locationIds)
                    ->whereRaw('
                        ST_Distance_Sphere(
                            point(leads.longitude, leads.latitude),
                            point(store_locations.longitude, store_locations.latitude)
                        ) * 0.001 <= store_locations.coverage_radius
                    ');
            })
            ->whereNotIn('leads.id', function ($query): void {
                $query->select('lead_id')
                    ->from('lead_stores')
                    ->whereIn('store_id', function ($q): void {
                        $q->select('id')
                            ->from('stores')
                            ->where('company_id', $this->company_id);
                    });
            })
            ->where('leads.status', 'new')
            ->groupBy('leads.id')
            ->orderBy('min_distance_in_km');
    }

    /**
     * Retorna a localização principal da loja
     */
    public function mainLocation()
    {
        return $this->locations()
            ->where('is_main', true)
            ->first();
    }

    /**
     * Verifica se a loja tem contrato ativo
     */
    public function hasActiveContract(): bool
    {
        return $this->contracts()
            ->active()
            ->exists();
    }

    /**
     * Escopo para lojas com contrato ativo
     */
    public function scopeWithActiveContract($query)
    {
        return $query->whereHas('contracts', function ($query): void {
            $query->active();
        });
    }
}
