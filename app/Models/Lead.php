<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\HasGeolocation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Modelo de Lead
 *
 * Representa os potenciais clientes que são captados e distribuídos para as lojas.
 * Inclui funcionalidades para geolocalização, restrições de distribuição, e relacionamentos com lojas.
 */
final class Lead extends BaseModel
{
    use HasGeolocation;

    /**
     * Período padrão de restrição em meses
     *
     * Define por quanto tempo um lead não pode ser reenviado para a mesma empresa/loja
     *
     * @var int
     */
    protected static int $restrictionPeriodMonths = 3;

    /**
     * Atributos que são permitidos para atribuição em massa
     *
     * @var array<int, string>
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
     *
     * @var array<string, string>
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
     * Define o período de restrição para distribuição de leads
     *
     * Configura por quantos meses um lead não pode ser reenviado para a mesma empresa/loja
     *
     * @param int $months Número de meses para o período de restrição
     * @throws InvalidArgumentException Se o número de meses for menor que 1
     * @return void
     */
    public static function setRestrictionPeriod(int $months): void
    {
        if ($months < 1) {
            throw new InvalidArgumentException('O período de restrição deve ser de pelo menos 1 mês');
        }
        self::$restrictionPeriodMonths = $months;
    }

    /**
     * Retorna o período de restrição atual em meses
     *
     * @return int Número de meses configurado para restrição
     */
    public static function getRestrictionPeriod(): int
    {
        return self::$restrictionPeriodMonths;
    }

    /**
     * Define o relacionamento com os telefones do lead
     *
     * Um lead pode ter múltiplos telefones associados
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany Relacionamento com os telefones
     */
    public function phones()
    {
        return $this->hasMany(LeadPhone::class);
    }

    /**
     * Encontra lojas elegíveis para receber este lead
     *
     * Considera restrições de telefone, distância geográfica e
     * contratos ativos para determinar elegibilidade
     *
     * @return \Illuminate\Database\Eloquent\Builder Query com as lojas elegíveis ordenadas por distância
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
     * Define o relacionamento com as lojas que receberam este lead
     *
     * Relação many-to-many com a tabela pivô lead_stores que contém
     * informações adicionais sobre o envio do lead
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany Relacionamento com as lojas
     */
    public function stores()
    {
        return $this->belongsToMany(Store::class, 'lead_stores')
            ->withPivot(['contract_id', 'status', 'sent_at', 'viewed_at', 'contacted_at', 'converted_at', 'returned_at'])
            ->withTimestamps();
    }

    /**
     * Verifica se um lead já foi enviado para uma loja específica no período de restrição
     *
     * @param Store $store A loja a ser verificada
     * @return bool Verdadeiro se o lead já foi enviado para a loja dentro do período de restrição
     */
    public function hasBeenSentToStore(Store $store): bool
    {
        return $this->checkLeadRestriction()
            ->where('store_id', $store->id)
            ->exists();
    }

    /**
     * Verifica se um lead já foi enviado para qualquer loja de uma empresa no período de restrição
     *
     * @param Company $company A empresa a ser verificada
     * @return bool Verdadeiro se o lead já foi enviado para alguma loja da empresa dentro do período de restrição
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
     * Método de inicialização do modelo
     *
     * Configura eventos que são disparados durante o ciclo de vida do modelo,
     * como normalização automática de telefones ao criar um lead
     *
     * @return void
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
     * Cria uma query base para verificar restrições de distribuição de leads
     *
     * Método auxiliar utilizado para verificar restrições com base nos telefones do lead
     *
     * @return \Illuminate\Database\Eloquent\Builder Query base para verificação de restrições
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
