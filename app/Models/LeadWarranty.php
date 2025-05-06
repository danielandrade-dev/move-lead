<?php

declare(strict_types=1);

namespace App\Models;

use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Modelo de Garantia de Lead
 *
 * Representa o processo de garantia para leads com problemas.
 * Gerencia todo o ciclo de vida da garantia, desde a solicitação até a
 * substituição ou rejeição do lead.
 */
final class LeadWarranty extends BaseModel
{
    /**
     * Status possíveis para a garantia
     *
     * Constantes que definem os possíveis estados de uma garantia no sistema
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_WAITING_REPLACEMENT = 'waiting_replacement';
    public const STATUS_REPLACED = 'replaced';

    /**
     * Atributos que são permitidos para atribuição em massa
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'lead_store_id',
        'new_lead_id',
        'status',
        'return_reason',
        'analysis_notes',
        'analyzed_by',
        'analyzed_at',
        'replaced_at',
        'is_active',
    ];

    /**
     * Atributos que devem ser convertidos para tipos nativos
     *
     * @var array<string, string>
     */
    protected $casts = [
        'lead_store_id' => 'integer',
        'new_lead_id' => 'integer',
        'analyzed_by' => 'integer',
        'analyzed_at' => 'datetime',
        'replaced_at' => 'datetime',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Retorna a lista de status possíveis com seus rótulos
     *
     * @return array<string, string> Matriz associativa com os status possíveis e seus nomes legíveis
     */
    public static function getStatusList(): array
    {
        return [
            self::STATUS_PENDING => 'Pendente',
            self::STATUS_APPROVED => 'Aprovado',
            self::STATUS_REJECTED => 'Rejeitado',
            self::STATUS_WAITING_REPLACEMENT => 'Aguardando Substituição',
            self::STATUS_REPLACED => 'Substituído',
        ];
    }

    /**
     * Define o relacionamento com o LeadStore original
     *
     * Uma garantia está associada a um único registro de lead-loja
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relacionamento com o LeadStore
     */
    public function leadStore()
    {
        return $this->belongsTo(LeadStore::class);
    }

    /**
     * Define o relacionamento com o novo Lead (substituição)
     *
     * Uma garantia pode estar associada a um novo lead quando substituída
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relacionamento com o novo Lead
     */
    public function newLead()
    {
        return $this->belongsTo(Lead::class, 'new_lead_id');
    }

    /**
     * Define o relacionamento com o analista que avaliou a garantia
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relacionamento com o usuário analista
     */
    public function analyst()
    {
        return $this->belongsTo(User::class, 'analyzed_by');
    }

    /**
     * Processa a aprovação da garantia
     *
     * Atualiza o status para aguardando substituição e incrementa o contador
     * de garantias utilizadas no contrato
     *
     * @param User $analyst Usuário analista que aprovou a garantia
     * @param string|null $notes Notas adicionais sobre a aprovação
     * @throws Exception Se o limite de garantia do contrato foi atingido
     * @return void
     */
    public function approve(User $analyst, ?string $notes = null): void
    {
        DB::transaction(function () use ($analyst, $notes): void {
            $contract = $this->leadStore->contract;

            if ( ! $contract->hasReachedWarrantyLimit()) {
                $this->update([
                    'status' => self::STATUS_WAITING_REPLACEMENT,
                    'analysis_notes' => $notes,
                    'analyzed_by' => $analyst->id,
                    'analyzed_at' => now(),
                ]);

                $contract->processLeadReturn($this->leadStore->lead);
            } else {
                throw new Exception('Limite de garantia atingido para este contrato');
            }
        });
    }

    /**
     * Rejeita a solicitação de garantia
     *
     * Atualiza o status para rejeitado e registra o analista e as notas
     *
     * @param User $analyst Usuário analista que rejeitou a garantia
     * @param string $notes Notas explicando o motivo da rejeição
     * @return void
     */
    public function reject(User $analyst, string $notes): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'analysis_notes' => $notes,
            'analyzed_by' => $analyst->id,
            'analyzed_at' => now(),
        ]);
    }

    /**
     * Atribui um novo lead como substituição para a garantia
     *
     * Cria um novo registro LeadStore para o lead substituto e
     * atualiza o status da garantia para substituído
     *
     * @param Lead $newLead O novo lead que substituirá o original
     * @return void
     */
    public function assignReplacementLead(Lead $newLead): void
    {
        DB::transaction(function () use ($newLead): void {
            // Cria novo LeadStore para o lead de garantia
            LeadStore::create([
                'lead_id' => $newLead->id,
                'store_id' => $this->leadStore->store_id,
                'contract_id' => $this->leadStore->contract_id,
                'status' => LeadStore::STATUS_NEW,
                'is_warranty' => true,
            ]);

            $this->update([
                'new_lead_id' => $newLead->id,
                'status' => self::STATUS_REPLACED,
                'replaced_at' => now(),
            ]);
        });
    }

    /**
     * Verifica se a garantia está pendente de análise
     *
     * @return bool Verdadeiro se o status for pendente
     */
    public function isPending(): bool
    {
        return self::STATUS_PENDING === $this->status;
    }

    /**
     * Verifica se a garantia foi aprovada
     *
     * Considera aprovada se estiver em qualquer um dos estados pós-aprovação
     *
     * @return bool Verdadeiro se a garantia foi aprovada, aguarda substituição ou já foi substituída
     */
    public function isApproved(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_WAITING_REPLACEMENT, self::STATUS_REPLACED]);
    }

    /**
     * Verifica se a garantia foi rejeitada
     *
     * @return bool Verdadeiro se o status for rejeitado
     */
    public function isRejected(): bool
    {
        return self::STATUS_REJECTED === $this->status;
    }

    /**
     * Verifica se a garantia já foi substituída
     *
     * @return bool Verdadeiro se o status for substituído
     */
    public function isReplaced(): bool
    {
        return self::STATUS_REPLACED === $this->status;
    }

    /**
     * Escopo para filtrar garantias pendentes
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Query do Eloquent
     * @return \Illuminate\Database\Eloquent\Builder Query modificada
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Escopo para filtrar garantias aprovadas
     *
     * Inclui todos os estados após a aprovação
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Query do Eloquent
     * @return \Illuminate\Database\Eloquent\Builder Query modificada
     */
    public function scopeApproved($query)
    {
        return $query->whereIn('status', [
            self::STATUS_APPROVED,
            self::STATUS_WAITING_REPLACEMENT,
            self::STATUS_REPLACED,
        ]);
    }

    /**
     * Escopo para filtrar garantias rejeitadas
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Query do Eloquent
     * @return \Illuminate\Database\Eloquent\Builder Query modificada
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Escopo para filtrar garantias substituídas
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Query do Eloquent
     * @return \Illuminate\Database\Eloquent\Builder Query modificada
     */
    public function scopeReplaced($query)
    {
        return $query->where('status', self::STATUS_REPLACED);
    }
}
