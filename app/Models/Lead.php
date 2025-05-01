<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\HasGeolocation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class Lead extends BaseModel
{
    use HasGeolocation;

    /**
     * Período padrão de restrição em meses
     */
    protected static int $restrictionPeriodMonths = 3;

    /**
     * Atributos que são permitidos para atribuição em massa
     */
    protected $fillable = [
        'segment_id',
        'name',
        'email',
        'phone',
        'zip_code',
        'city',
        'state',
        'address',
        'latitude',
        'longitude',
        'status',
        'is_active',
    ];

    /**
     * Atributos que devem ser convertidos para tipos nativos
     */
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'segment_id' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Define o período de restrição
     */
    public static function setRestrictionPeriod(int $months): void
    {
        if ($months < 1) {
            throw new InvalidArgumentException('O período de restrição deve ser de pelo menos 1 mês');
        }
        self::$restrictionPeriodMonths = $months;
    }

    /**
     * Retorna o período de restrição atual
     */
    public static function getRestrictionPeriod(): int
    {
        return self::$restrictionPeriodMonths;
    }

    /**
     * Relacionamento com os telefones do lead
     */
    public function phones()
    {
        return $this->hasMany(LeadPhone::class);
    }

    /**
     * Encontra lojas elegíveis considerando restrição de telefone
     */
    public function findEligibleStores()
    {
        $normalizedPhones = $this->phones->pluck('phone_normalized');

        return Store::select('stores.*')
            ->join('store_locations', 'stores.id', '=', 'store_locations.store_id')
            ->selectRaw('
                MIN(
                    ST_Distance_Sphere(
                        point(store_locations.longitude, store_locations.latitude),
                        point(?, ?)
                    ) * 0.001
                ) as min_distance_in_km
            ', [$this->longitude, $this->latitude])
            ->withinRadius($this->latitude, $this->longitude, DB::raw('store_locations.coverage_radius'))
            ->whereHas('contracts', function ($query): void {
                $query->active();
            })
            ->active()
            ->whereHas('locations', function ($query): void {
                $query->active();
            })
            ->whereNotExists(function ($query) use ($normalizedPhones): void {
                $query->select(DB::raw(1))
                    ->from('lead_stores')
                    ->join('leads', 'lead_stores.lead_id', '=', 'leads.id')
                    ->join('lead_phones', 'leads.id', '=', 'lead_phones.lead_id')
                    ->where('lead_stores.store_id', DB::raw('stores.id'))
                    ->whereIn('lead_phones.phone_normalized', $normalizedPhones)
                    ->where('lead_stores.created_at', '>=', now()->subMonths(self::$restrictionPeriodMonths));
            })
            ->groupBy('stores.id')
            ->orderBy('min_distance_in_km');
    }

    /**
     * Verifica se um lead já foi enviado para uma loja no período
     */
    public function hasBeenSentToStore(Store $store): bool
    {
        return $this->checkLeadRestriction()
            ->where('store_id', $store->id)
            ->exists();
    }

    /**
     * Verifica se um lead já foi enviado para uma empresa no período
     */
    public function hasBeenSentToCompany(Company $company): bool
    {
        return $this->checkLeadRestriction()
            ->whereHas('store', function ($query) use ($company): void {
                $query->where('company_id', $company->id);
            })
            ->exists();
    }

    /**
     * Boot function from Laravel
     */
    protected static function boot(): void
    {
        parent::boot();

        static::created(function ($lead): void {
            $lead->phones()->create([
                'phone_normalized' => LeadPhone::normalizePhone($lead->phone),
                'phone_original' => $lead->phone,
            ]);
        });
    }

    /**
     * Query base para verificar restrições de lead
     */
    private function checkLeadRestriction()
    {
        $normalizedPhones = $this->phones->pluck('phone_normalized');

        return LeadStore::query()
            ->whereHas('lead.phones', function ($query) use ($normalizedPhones): void {
                $query->whereIn('phone_normalized', $normalizedPhones);
            })
            ->where('created_at', '>=', now()->subMonths(self::$restrictionPeriodMonths));
    }
}
