<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Store extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'document',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip_code',
        'is_active'
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
        return $this->morphMany(Contract::class, 'contractable');
    }

    public function activeContract()
    {
        return $this->contracts()->where('is_active', true)->first();
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
            ->join('store_locations', function($join) use ($locationIds) {
                $join->whereIn('store_locations.id', $locationIds)
                    ->whereRaw('
                        ST_Distance_Sphere(
                            point(leads.longitude, leads.latitude),
                            point(store_locations.longitude, store_locations.latitude)
                        ) * 0.001 <= store_locations.coverage_radius
                    ');
            })
            ->whereNotIn('leads.id', function($query) {
                $query->select('lead_id')
                    ->from('lead_stores')
                    ->whereIn('store_id', function($q) {
                        $q->select('id')
                            ->from('stores')
                            ->where('company_id', $this->company_id);
                    });
            })
            ->where('leads.status', 'new')
            ->groupBy('leads.id')
            ->orderBy('min_distance_in_km');
    }
}