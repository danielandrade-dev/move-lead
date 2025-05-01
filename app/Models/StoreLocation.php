<?php

declare(strict_types=1);

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class StoreLocation extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'name',
        'address',
        'city',
        'state',
        'zip_code',
        'latitude',
        'longitude',
        'coverage_radius',
        'is_active',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    // Método para encontrar leads dentro do raio de cobertura deste ponto
    public function findLeadsInRange()
    {
        return Lead::select('*')
            ->selectRaw('
                ST_Distance_Sphere(
                    point(longitude, latitude),
                    point(?, ?)
                ) * 0.001 as distance_in_km
            ', [$this->longitude, $this->latitude])
            ->havingRaw('distance_in_km <= ?', [$this->coverage_radius])
            ->whereNotIn('id', function ($query): void {
                $query->select('lead_id')
                    ->from('lead_stores')
                    ->whereIn('store_id', function ($q): void {
                        // Excluir lojas da mesma empresa
                        $q->select('id')
                            ->from('stores')
                            ->where('company_id', $this->store->company_id);
                    });
            })
            ->where('status', 'new')
            ->orderBy('distance_in_km');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($location): void {
            if ($location->coverage_radius < 10) {
                throw new Exception('O raio de cobertura mínimo é de 10km');
            }
        });
    }
}
