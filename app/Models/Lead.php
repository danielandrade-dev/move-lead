<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class Lead extends Model
{
    use HasFactory;
    use SoftDeletes;

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
    ];

    // Período padrão de restrição em meses
    protected static int $restrictionPeriodMonths = 3;

    // Método para alterar o período de restrição
    public static function setRestrictionPeriod(int $months): void
    {
        if ($months < 1) {
            throw new InvalidArgumentException('O período de restrição deve ser de pelo menos 1 mês');
        }
        self::$restrictionPeriodMonths = $months;
    }

    // Getter para o período de restrição
    public static function getRestrictionPeriod(): int
    {
        return self::$restrictionPeriodMonths;
    }

    public function phones()
    {
        return $this->hasMany(LeadPhone::class);
    }

    // Encontrar lojas elegíveis considerando restrição de telefone
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
            ->whereRaw('
                ST_Distance_Sphere(
                    point(store_locations.longitude, store_locations.latitude),
                    point(?, ?)
                ) * 0.001 <= store_locations.coverage_radius
            ', [$this->longitude, $this->latitude])
            ->whereHas('contracts', function ($query): void {
                $query->where('is_active', true);
            })
            ->where('stores.is_active', true)
            ->where('store_locations.is_active', true)
            // Excluir lojas que já receberam leads com os mesmos telefones no período
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

    // Verificar se um telefone já foi enviado para uma loja no período
    public function hasBeenSentToStore(Store $store): bool
    {
        $normalizedPhones = $this->phones->pluck('phone_normalized');

        return LeadStore::query()
            ->where('store_id', $store->id)
            ->whereHas('lead.phones', function ($query) use ($normalizedPhones): void {
                $query->whereIn('phone_normalized', $normalizedPhones);
            })
            ->where('created_at', '>=', now()->subMonths(self::$restrictionPeriodMonths))
            ->exists();
    }

    // Verificar se um telefone já foi enviado para uma empresa no período
    public function hasBeenSentToCompany(Company $company): bool
    {
        $normalizedPhones = $this->phones->pluck('phone_normalized');

        return LeadStore::query()
            ->whereHas('store', function ($query) use ($company): void {
                $query->where('company_id', $company->id);
            })
            ->whereHas('lead.phones', function ($query) use ($normalizedPhones): void {
                $query->whereIn('phone_normalized', $normalizedPhones);
            })
            ->where('created_at', '>=', now()->subMonths(self::$restrictionPeriodMonths))
            ->exists();
    }

    protected static function boot(): void
    {
        parent::boot();

        // Ao criar um lead, normaliza e salva o telefone
        static::created(function ($lead): void {
            $lead->phones()->create([
                'phone_normalized' => LeadPhone::normalizePhone($lead->phone),
                'phone_original' => $lead->phone,
            ]);
        });
    }
}
