<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Modelo de Campo de Segmento
 *
 * Representa os campos personalizados que podem ser definidos para cada segmento.
 * Gerencia tipos de dados, validações e ordenação dos campos.
 */
final class SegmentField extends Model
{
    /**
     * Tipos de campos possíveis
     *
     * Constantes que definem os tipos de dados suportados para campos personalizados
     */
    public const TYPE_TEXT = 'text';
    public const TYPE_NUMBER = 'number';
    public const TYPE_DATE = 'date';
    public const TYPE_SELECT = 'select';
    public const TYPE_BOOLEAN = 'boolean';

    /**
     * Atributos que são permitidos para atribuição em massa
     *
     * @var array<int, string>
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
     *
     * @var array<string, string>
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
     * Retorna a lista de tipos de campos disponíveis com seus rótulos
     *
     * @return array<string, string> Matriz associativa com os tipos de campos e seus nomes legíveis
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
     * Define o relacionamento com o segmento
     *
     * Um campo pertence a um único segmento
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relacionamento com o segmento
     */
    public function segment()
    {
        return $this->belongsTo(Segments::class, 'segment_id');
    }

    /**
     * Verifica se o campo é do tipo seleção
     *
     * Campos do tipo seleção requerem opções pré-definidas
     *
     * @return bool Verdadeiro se o campo for do tipo seleção
     */
    public function isSelectType(): bool
    {
        return self::TYPE_SELECT === $this->type;
    }

    /**
     * Verifica se o campo é obrigatório
     *
     * @return bool Verdadeiro se o campo for obrigatório
     */
    public function isRequired(): bool
    {
        return $this->is_required;
    }

    /**
     * Escopo para ordenar campos pela ordem definida
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Query do Eloquent
     * @return \Illuminate\Database\Eloquent\Builder Query modificada
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Método de inicialização do modelo
     *
     * Configura eventos para validação de campos do tipo seleção
     * e definição automática da ordem
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($field): void {
            // Garante que campos do tipo select tenham opções
            if ($field->isSelectType() && empty($field->options)) {
                throw new InvalidArgumentException('Campos do tipo seleção devem ter opções definidas');
            }

            // Define a ordem padrão se não informada
            if ( ! isset($field->order)) {
                $field->order = static::where('segment_id', $field->segment_id)->max('order') + 1;
            }
        });
    }
}
