<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Modelo de Campo Personalizado de Lead
 *
 * Representa os valores de campos personalizados associados a um lead.
 * Gerencia validações, formatações e relacionamentos com SegmentField.
 */
final class LeadCustomField extends Model
{
    /**
     * Atributos que são permitidos para atribuição em massa
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'lead_id',
        'segment_field_id',
        'value',
        'is_active',
    ];

    /**
     * Atributos que devem ser convertidos para tipos nativos
     *
     * @var array<string, string>
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
     * Define o relacionamento com o lead
     *
     * Um campo personalizado pertence a um único lead
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relacionamento com o lead
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Define o relacionamento com o campo do segmento
     *
     * Um campo personalizado é baseado em um campo de segmento
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relacionamento com o campo do segmento
     */
    public function segmentField()
    {
        return $this->belongsTo(SegmentField::class);
    }

    /**
     * Retorna o valor formatado do campo baseado no tipo
     *
     * Converte o valor para um formato legível de acordo com o tipo do campo
     *
     * @return mixed Valor formatado de acordo com o tipo do campo
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
     *
     * Verifica se o valor é válido para o tipo de campo e se atende aos
     * requisitos de obrigatoriedade
     *
     * @return bool Verdadeiro se o valor for válido
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
     * Método de inicialização do modelo
     *
     * Configura eventos para validação automática do valor antes de salvar
     *
     * @return void
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
