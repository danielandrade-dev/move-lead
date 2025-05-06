<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\HasCommonAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Classe abstrata base para todos os modelos da aplicação.
 *
 * Fornece funcionalidades comuns como soft deletes, atributos compartilhados,
 * e configurações padrão para todos os modelos derivados.
 */
abstract class BaseModel extends Model
{
    use HasCommonAttributes;
    use HasFactory;
    use SoftDeletes;

    /**
     * Indica se o model deve usar timestamps
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Atributos que devem ser convertidos para tipos nativos
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Atributos que são permitidos para atribuição em massa
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'is_active',
    ];

    /**
     * Método de inicialização do modelo
     *
     * Executado automaticamente quando o modelo é inicializado.
     * Define comportamentos padrão para todos os modelos derivados.
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        // Garante que registros criados são ativos por padrão
        static::creating(function ($model): void {
            if ( ! isset($model->is_active)) {
                $model->is_active = true;
            }
        });
    }
}
