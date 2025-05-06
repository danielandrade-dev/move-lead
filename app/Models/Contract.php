<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Modelo de Contrato
 *
 * Representa contratos de fornecimento de leads para empresas ou lojas.
 * Gerencia o ciclo de vida do contrato, incluindo datas, limites, entregas e garantias.
 */
final class Contract extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Atributos que são permitidos para atribuição em massa
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'contractable_type',
        'contractable_id',
        'start_date',
        'end_date',
        'lead_price',
        'leads_contracted',
        'leads_delivered',
        'leads_returned',
        'leads_warranty_used',
        'warranty_percentage',
        'is_active',
        'completed_at',
        'auto_close_at',
    ];

    /**
     * Atributos que devem ser tratados como datas
     *
     * @var array<int, string>
     */
    protected $dates = [
        'start_date',
        'end_date',
        'completed_at',
        'auto_close_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Atributos que devem ser convertidos para tipos nativos
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'lead_price' => 'decimal:2',
        'leads_contracted' => 'integer',
        'leads_delivered' => 'integer',
        'leads_returned' => 'integer',
        'leads_warranty_used' => 'integer',
        'warranty_percentage' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Define o relacionamento polimórfico com a entidade contratante
     *
     * O contrato pode pertencer a uma empresa ou a uma loja
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo Relacionamento morfológico com a entidade contratante
     */
    public function contractable()
    {
        return $this->morphTo();
    }

    /**
     * Define o relacionamento com os leads associados a este contrato
     *
     * Um contrato pode ter múltiplos leads associados através da tabela lead_stores
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany Relacionamento com as associações lead-loja
     */
    public function leadStores()
    {
        return $this->hasMany(LeadStore::class);
    }

    /**
     * Calcula a quantidade de leads de garantia ainda disponíveis
     *
     * Baseado na quantidade contratada, percentual de garantia e leads já utilizados
     *
     * @return int Número de leads de garantia disponíveis
     */
    public function getAvailableWarrantyLeadsAttribute()
    {
        $maxWarrantyLeads = ceil($this->leads_contracted * ($this->warranty_percentage / 100));
        return max(0, $maxWarrantyLeads - $this->leads_warranty_used);
    }

    /**
     * Verifica se o contrato atingiu o limite de leads de garantia
     *
     * @return bool Verdadeiro se não houver mais leads de garantia disponíveis
     */
    public function hasReachedWarrantyLimit(): bool
    {
        return $this->available_warranty_leads <= 0;
    }

    /**
     * Verifica se o contrato atingiu o total de leads contratados
     *
     * @return bool Verdadeiro se a quantidade de leads entregues é maior ou igual à contratada
     */
    public function isComplete(): bool
    {
        return $this->leads_delivered >= $this->leads_contracted;
    }

    /**
     * Incrementa o contador de leads entregues e atualiza o status do contrato
     *
     * Se o contrato estiver completo após a incrementação, verifica se deve ser finalizado
     * ou se deve ter um fechamento automático agendado
     *
     * @return void
     */
    public function incrementDeliveredLeads(): void
    {
        DB::transaction(function (): void {
            $this->leads_delivered++;

            if ($this->isComplete()) {
                if ($this->hasReachedWarrantyLimit()) {
                    $this->completeContract();
                } else {
                    $this->scheduleAutoClose();
                }
            }

            $this->save();
        });
    }

    /**
     * Finaliza o contrato, marcando como inativo e registrando a data de conclusão
     *
     * @return void
     */
    public function completeContract(): void
    {
        $this->is_active = false;
        $this->completed_at = now();
        $this->auto_close_at = null;
        $this->save();
    }

    /**
     * Processa a devolução de um lead, incrementando contadores de garantia
     *
     * Verifica se ainda há leads de garantia disponíveis e atualiza os contadores
     *
     * @param Lead $lead O lead que está sendo devolvido
     * @return bool Verdadeiro se o processamento foi bem-sucedido, falso se não há leads de garantia disponíveis
     */
    public function processLeadReturn(Lead $lead)
    {
        if ( ! $this->hasReachedWarrantyLimit()) {
            DB::transaction(function (): void {
                $this->leads_returned++;
                $this->leads_warranty_used++;

                if ($this->hasReachedWarrantyLimit() && $this->isComplete()) {
                    $this->completeContract();
                }

                $this->save();
            });

            return true;
        }

        return false;
    }

    /**
     * Calcula a quantidade de leads restantes a serem entregues
     *
     * @return int Número de leads restantes no contrato
     */
    public function getRemainingLeadsAttribute()
    {
        return max(0, $this->leads_contracted - $this->leads_delivered);
    }

    /**
     * Calcula o percentual de uso da garantia em relação aos leads contratados
     *
     * @return float Percentual de uso da garantia (0-100)
     */
    public function getWarrantyUsagePercentageAttribute()
    {
        if (0 === $this->leads_contracted) {
            return 0;
        }
        return ($this->leads_warranty_used / $this->leads_contracted) * 100;
    }

    /**
     * Escopo para filtrar contratos ativos
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Query do Eloquent
     * @return \Illuminate\Database\Eloquent\Builder Query modificada
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Escopo para filtrar contratos pendentes de fechamento automático
     *
     * Contratos ativos que têm data de fechamento automático definida e
     * essa data já foi atingida ou ultrapassada
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Query do Eloquent
     * @return \Illuminate\Database\Eloquent\Builder Query modificada
     */
    public function scopePendingAutoClose($query)
    {
        return $query->where('is_active', true)
            ->whereNotNull('auto_close_at')
            ->where('auto_close_at', '<=', now());
    }

    /**
     * Método de inicialização do modelo
     *
     * Configura eventos para validações e valores padrão
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($contract): void {
            if ( ! isset($contract->warranty_percentage)) {
                $contract->warranty_percentage = 30;
            }
        });

        static::saving(function ($contract): void {
            if ($contract->start_date > $contract->end_date) {
                throw new InvalidArgumentException('A data de início deve ser anterior à data de término');
            }

            if (null !== $contract->leads_contracted && $contract->leads_contracted < 1) {
                throw new InvalidArgumentException('O número de leads contratados deve ser maior que zero');
            }

            if (null !== $contract->lead_price && $contract->lead_price < 0) {
                throw new InvalidArgumentException('O preço dos leads não pode ser negativo');
            }
        });
    }

    /**
     * Agenda o fechamento automático do contrato para daqui a 7 dias
     *
     * Utilizado quando o contrato está completo mas ainda tem garantia disponível
     *
     * @return void
     */
    protected function scheduleAutoClose(): void
    {
        $this->auto_close_at = now()->addDays(7);
        $this->save();
    }
}
