<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final class LeadStore extends Model
{
    /**
     * Status possíveis para o lead
     */
    public const STATUS_NEW = 'new';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_CONVERTED = 'converted';
    public const STATUS_NOT_INTERESTED = 'not_interested';
    public const STATUS_INVALID = 'invalid';
    public const STATUS_WARRANTY_PENDING = 'warranty_pending';
    public const STATUS_WARRANTY_APPROVED = 'warranty_approved';
    public const STATUS_WARRANTY_REJECTED = 'warranty_rejected';
    public const STATUS_WARRANTY_WAITING_REPLACEMENT = 'warranty_waiting_replacement';
    public const STATUS_WARRANTY_REPLACED = 'warranty_replaced';
    /**
     * Atributos que são permitidos para atribuição em massa
     */
    protected $fillable = [
        'lead_id',
        'store_id',
        'status',
        'notes',
        'is_active',
    ];

    /**
     * Atributos que devem ser convertidos para tipos nativos
     */
    protected $casts = [
        'lead_id' => 'integer',
        'store_id' => 'integer',
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
            self::STATUS_NEW => 'Novo',
            self::STATUS_CONTACTED => 'Contatado',
            self::STATUS_CONVERTED => 'Convertido',
            self::STATUS_NOT_INTERESTED => 'Não Interessado',
            self::STATUS_INVALID => 'Inválido',
            self::STATUS_WARRANTY_PENDING => 'Garantia Pendente',
            self::STATUS_WARRANTY_APPROVED => 'Garantia Aprovada',
            self::STATUS_WARRANTY_REJECTED => 'Garantia Rejeitada',
            self::STATUS_WARRANTY_WAITING_REPLACEMENT => 'Garantia Aguardando Substituição',
            self::STATUS_WARRANTY_REPLACED => 'Garantia Substituída',
        ];
    }

    /**
     * Relacionamento com o lead
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Relacionamento com a loja
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Escopo para leads com determinado status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Escopo para leads convertidos
     */
    public function scopeConverted($query)
    {
        return $query->where('status', self::STATUS_CONVERTED);
    }

    /**
     * Escopo para leads não convertidos
     */
    public function scopeNotConverted($query)
    {
        return $query->where('status', '!=', self::STATUS_CONVERTED);
    }

    /**
     * Verifica se o lead foi convertido
     */
    public function isConverted(): bool
    {
        return self::STATUS_CONVERTED === $this->status;
    }

    /**
     * Atualiza o status do lead
     */
    public function updateStatus(string $status, ?string $notes = null): void
    {
        if ( ! array_key_exists($status, self::getStatusList())) {
            throw new InvalidArgumentException('Status inválido');
        }

        $this->update([
            'status' => $status,
            'notes' => $notes,
        ]);
    }

}
