<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Modelo de Associação entre Lead e Loja
 *
 * Representa a relação entre um lead e uma loja, incluindo o status do lead
 * e todo o histórico de interação entre a loja e o potencial cliente.
 * Gerencia também o processo de garantia e substituição de leads.
 */
final class LeadStore extends Model
{
    /**
     * Status possíveis para o lead
     *
     * Constantes que definem os possíveis estados de um lead no sistema
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
     *
     * @var array<int, string>
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
     *
     * @var array<string, string>
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
     * Retorna a lista de status possíveis com seus rótulos
     *
     * @return array<string, string> Matriz associativa com os status possíveis e seus nomes legíveis
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
     * Define o relacionamento com o lead
     *
     * Uma associação lead-loja pertence a um único lead
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relacionamento com o lead
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Define o relacionamento com a loja
     *
     * Uma associação lead-loja pertence a uma única loja
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relacionamento com a loja
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Escopo para filtrar associações por status específico
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Query do Eloquent
     * @param string $status Status a ser filtrado
     * @return \Illuminate\Database\Eloquent\Builder Query modificada
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Escopo para filtrar apenas leads convertidos
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Query do Eloquent
     * @return \Illuminate\Database\Eloquent\Builder Query modificada
     */
    public function scopeConverted($query)
    {
        return $query->where('status', self::STATUS_CONVERTED);
    }

    /**
     * Escopo para filtrar leads não convertidos
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Query do Eloquent
     * @return \Illuminate\Database\Eloquent\Builder Query modificada
     */
    public function scopeNotConverted($query)
    {
        return $query->where('status', '!=', self::STATUS_CONVERTED);
    }

    /**
     * Verifica se o lead foi convertido
     *
     * @return bool Verdadeiro se o status for 'convertido', falso caso contrário
     */
    public function isConverted(): bool
    {
        return self::STATUS_CONVERTED === $this->status;
    }

    /**
     * Atualiza o status do lead e adiciona notas opcionais
     *
     * @param string $status Novo status a ser definido
     * @param string|null $notes Notas adicionais sobre a mudança de status (opcional)
     * @throws InvalidArgumentException Se o status fornecido não for válido
     * @return void
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
