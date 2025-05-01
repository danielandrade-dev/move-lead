<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class SegmentField extends Model
{
    /**
     * Tipos de campos possíveis
     */
    public const TYPE_TEXT = 'text';
    public const TYPE_NUMBER = 'number';
    public const TYPE_DATE = 'date';
    public const TYPE_SELECT = 'select';
    public const TYPE_BOOLEAN = 'boolean';

    /**
     * Atributos que são permitidos para atribuição em massa
     */
    protected $fillable = [
        'segment_id',
        'name',
        'type',
        'description',
        'options',
        'is_required',
        'validation_rules',
        'order',
        'is_active',
    ];

    /**
     * Atributos que devem ser convertidos para tipos nativos
     */
    protected $casts = [
        'segment_id' => 'integer',
        'is_required' => 'boolean',
        'options' => 'array',
        'validation_rules' => 'array',
        'order' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Lista de tipos de campos disponíveis
     */
    public static function getTypesList(): array
    {
        return [
            self::TYPE_TEXT => 'Texto',
            self::TYPE_NUMBER => 'Número',
            self::TYPE_DATE => 'Data',
            self::TYPE_SELECT => 'Seleção',
            self::TYPE_BOOLEAN => 'Sim/Não',
        ];
    }

    /**
     * Relacionamento com o segmento
     */
    public function segment()
    {
        return $this->belongsTo(Segments::class, 'segment_id');
    }

    /**
     * Verifica se o campo é do tipo seleção
     */
    public function isSelectType(): bool
    {
        return $this->type === self::TYPE_SELECT;
    }

    /**
     * Verifica se o campo é obrigatório
     */
    public function isRequired(): bool
    {
        return $this->is_required;
    }

    /**
     * Escopo para ordenar campos por ordem
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Boot function from Laravel
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($field): void {
            // Garante que campos do tipo select tenham opções
            if ($field->isSelectType() && empty($field->options)) {
                throw new \InvalidArgumentException('Campos do tipo seleção devem ter opções definidas');
            }

            // Define a ordem padrão se não informada
            if (!isset($field->order)) {
                $field->order = static::where('segment_id', $field->segment_id)->max('order') + 1;
            }
        });
    }
}
