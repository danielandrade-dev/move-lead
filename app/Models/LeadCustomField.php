<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final class LeadCustomField extends Model
{
    /**
     * Atributos que são permitidos para atribuição em massa
     */
    protected $fillable = [
        'lead_id',
        'segment_field_id',
        'value',
        'is_active',
    ];

    /**
     * Atributos que devem ser convertidos para tipos nativos
     */
    protected $casts = [
        'lead_id' => 'integer',
        'segment_field_id' => 'integer',
        'value' => 'json',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relacionamento com o lead
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Relacionamento com o campo do segmento
     */
    public function segmentField()
    {
        return $this->belongsTo(SegmentField::class);
    }

    /**
     * Retorna o valor formatado do campo baseado no tipo
     */
    public function getFormattedValue()
    {
        $field = $this->segmentField;
        $value = $this->value;

        return match ($field->type) {
            SegmentField::TYPE_DATE => $value ? date('d/m/Y', strtotime($value)) : null,
            SegmentField::TYPE_BOOLEAN => $value ? 'Sim' : 'Não',
            SegmentField::TYPE_SELECT => $value,
            default => $value,
        };
    }

    /**
     * Valida o valor do campo de acordo com as regras do SegmentField
     */
    public function validateValue(): bool
    {
        $field = $this->segmentField;

        // Verifica se é obrigatório
        if ($field->isRequired() && empty($this->value)) {
            return false;
        }

        // Valida de acordo com o tipo
        return match ($field->type) {
            SegmentField::TYPE_NUMBER => is_numeric($this->value),
            SegmentField::TYPE_DATE => false !== strtotime($this->value),
            SegmentField::TYPE_SELECT => in_array($this->value, $field->options ?? []),
            SegmentField::TYPE_BOOLEAN => is_bool($this->value) || in_array($this->value, [0, 1, '0', '1']),
            default => true,
        };
    }

    /**
     * Boot function from Laravel
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($customField): void {
            if ( ! $customField->validateValue()) {
                throw new InvalidArgumentException(
                    'O valor fornecido é inválido para o tipo de campo',
                );
            }
        });
    }
}
