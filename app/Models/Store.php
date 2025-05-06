<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\HasGeolocation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo de Loja
 *
 * Representa as lojas que recebem e trabalham com leads.
 * Inclui funcionalidades para geolocalização, gestão de contratos
 * e integração com usuários e localizações de captação.
 */
final class Store extends Model
{
    use HasFactory;
    use HasGeolocation;
    use SoftDeletes;

    /**
     * Atributos que são permitidos para atribuição em massa
     *
     * @var array<int, string>
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
     *
     * @var array<string, string>
     */
    protected $casts = [
        'company_id' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Define o relacionamento com as localizações da loja
     *
     * Uma loja pode ter múltiplas localizações/pontos de captação
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany Relacionamento com as localizações
     */
    public function locations()
    {
        return $this->hasMany(StoreLocation::class);
    }

    /**
     * Define o relacionamento com a empresa
     *
     * Uma loja pertence a uma única empresa
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relacionamento com a empresa
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Define o relacionamento com os usuários da loja
     *
     * Uma loja pode ter múltiplos usuários associados
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany Relacionamento com os usuários
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Define o relacionamento com os contratos da loja
     *
     * Uma loja pode ter múltiplos contratos (ativos ou inativos)
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany Relacionamento morfológico com os contratos
     */
    public function contracts()
    {
        return $this->morphMany(Contract::class, 'contractable');
    }

    /**
     * Retorna o contrato ativo mais recente da loja
     *
     * Utiliza o relacionamento de contratos filtrando apenas os ativos
     * e ordenando pelo mais recente
     *
     * @return Contract|null O contrato ativo mais recente ou null se não existir
     */
    public function activeContract()
    {
        return $this->contracts()
            ->where('is_active', true)
            ->latest()
            ->first();
    }

    /**
     * Encontra leads elegíveis para a loja considerando todos os pontos de captação
     *
     * Utiliza a distância geográfica, o raio de cobertura e verifica se o lead
     * já foi enviado para a empresa da loja
     *
     * @return \Illuminate\Database\Eloquent\Builder|Collection Query com os leads elegíveis ou coleção vazia
     */
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
     *
     * Localização marcada como principal (is_main = true)
     *
     * @return StoreLocation|null A localização principal ou null se não existir
     */
    public function mainLocation()
    {
        return $this->locations()
            ->where('is_main', true)
            ->first();
    }

    /**
     * Verifica se a loja tem contrato ativo
     *
     * @return bool Verdadeiro se a loja tiver pelo menos um contrato ativo
     */
    public function hasActiveContract(): bool
    {
        return $this->contracts()
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Escopo para filtrar lojas com contrato ativo
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Query do Eloquent
     * @return \Illuminate\Database\Eloquent\Builder Query modificada
     */
    public function scopeWithActiveContract($query)
    {
        return $query->whereHas('contracts', function ($query): void {
            $query->where('is_active', true);
        });
    }

    /**
     * Escopo para filtrar lojas ativas
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Query do Eloquent
     * @return \Illuminate\Database\Eloquent\Builder Query modificada
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
