<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\HasCommonAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

abstract class BaseModel extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasCommonAttributes;

    /**
     * Indica se o model deve usar timestamps
     */
    public $timestamps = true;

    /**
     * Atributos que devem ser convertidos para tipos nativos
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Atributos que são permitidos para atribuição em massa
     */
    protected $fillable = [
        'is_active',
    ];

    /**
     * Boot function from Laravel
     */
    protected static function boot(): void
    {
        parent::boot();

        // Garante que registros criados são ativos por padrão
        static::creating(function ($model): void {
            if (!isset($model->is_active)) {
                $model->is_active = true;
            }
        });
    }
}