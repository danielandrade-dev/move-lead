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
        $lat = $this->latitude;
        $lng = $this->longitude;
        $point = "ST_SetSRID(ST_MakePoint($lng, $lat), 4326)";

        return Store::query()
            ->select('stores.*')
            ->addSelect(DB::raw("
                ST_Distance(
                    store_locations.geom,
                    $point
                ) * 0.001 as distance
            "))
            ->join('store_locations', function ($join) use ($point) {
                $join->on('stores.id', '=', 'store_locations.store_id')
                    ->whereRaw("ST_Distance(store_locations.geom, $point) * 0.001 <= store_locations.coverage_radius")
                    ->where('store_locations.is_active', true);
            })
            ->whereHas('contracts', function ($query) {
                $query->active();
            })
            ->where('stores.is_active', true)
            ->whereNotExists(function ($query) use ($normalizedPhones) {
                $query->select(DB::raw(1))
                    ->from('lead_stores')
                    ->join('leads', 'lead_stores.lead_id', '=', 'leads.id')
                    ->join('lead_phones', 'leads.id', '=', 'lead_phones.lead_id')
                    ->where('lead_stores.store_id', DB::raw('stores.id'))
                    ->whereIn('lead_phones.phone_normalized', $normalizedPhones)
                    ->where('lead_stores.created_at', '>=', now()->subMonths(self::$restrictionPeriodMonths));
            })
            ->orderBy('distance')
            ->distinct('stores.id');
    }

    /**
     * Relacionamento com lojas que receberam este lead
     */
    public function stores()
    {
        return $this->belongsToMany(Store::class, 'lead_stores')
            ->withPivot(['contract_id', 'status', 'sent_at', 'viewed_at', 'contacted_at', 'converted_at', 'returned_at'])
            ->withTimestamps();
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
