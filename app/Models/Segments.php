<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Segments extends Model
{
    /**
     * Atributos que são permitidos para atribuição em massa
     */
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    /**
     * Atributos que devem ser convertidos para tipos nativos
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relacionamento com os campos do segmento
     */
    public function fields()
    {
        return $this->hasMany(SegmentField::class, 'segment_id');
    }

    /**
     * Relacionamento com os leads do segmento
     */
    public function leads()
    {
        return $this->hasMany(Lead::class, 'segment_id');
    }

    /**
     * Retorna os campos ativos do segmento
     */
    public function activeFields()
    {
        return $this->fields()->active();
    }

    /**
     * Verifica se o segmento possui leads associados
     */
    public function hasLeads(): bool
    {
        return $this->leads()->exists();
    }

    /**
     * Boot function from Laravel
     */
    protected static function boot(): void
    {
        parent::boot();

        // Impede a exclusão de segmentos com leads associados
        static::deleting(function ($segment): void {
            if ($segment->hasLeads()) {
                throw new \RuntimeException('Não é possível excluir um segmento que possui leads associados');
            }
        });
    }
}
