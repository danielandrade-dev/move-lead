<?php

declare(strict_types=1);

namespace App\Models;

use Exception;
use Illuminate\Support\Facades\DB;

final class LeadWarranty extends BaseModel
{
    /**
     * Status possíveis para a garantia
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_WAITING_REPLACEMENT = 'waiting_replacement';
    public const STATUS_REPLACED = 'replaced';

    /**
     * Atributos que são permitidos para atribuição em massa
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
     * Lista de status possíveis
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
     * Relacionamento com o LeadStore original
     */
    public function leadStore()
    {
        return $this->belongsTo(LeadStore::class);
    }

    /**
     * Relacionamento com o novo Lead (substituição)
     */
    public function newLead()
    {
        return $this->belongsTo(Lead::class, 'new_lead_id');
    }

    /**
     * Relacionamento com o analista
     */
    public function analyst()
    {
        return $this->belongsTo(User::class, 'analyzed_by');
    }

    /**
     * Processa a aprovação da garantia
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
     * Atribui um novo lead como substituição
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
     */
    public function isPending(): bool
    {
        return self::STATUS_PENDING === $this->status;
    }

    /**
     * Verifica se a garantia foi aprovada
     */
    public function isApproved(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_WAITING_REPLACEMENT, self::STATUS_REPLACED]);
    }

    /**
     * Verifica se a garantia foi rejeitada
     */
    public function isRejected(): bool
    {
        return self::STATUS_REJECTED === $this->status;
    }

    /**
     * Verifica se a garantia já foi substituída
     */
    public function isReplaced(): bool
    {
        return self::STATUS_REPLACED === $this->status;
    }

    /**
     * Escopo para garantias pendentes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Escopo para garantias aprovadas
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
     * Escopo para garantias rejeitadas
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Escopo para garantias substituídas
     */
    public function scopeReplaced($query)
    {
        return $query->where('status', self::STATUS_REPLACED);
    }
}
