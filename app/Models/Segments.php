<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use RuntimeException;

final class Segments extends Model
{
    use HasFactory;

    /**
     * Atributos que são permitidos para atribuição em massa
     */
    protected $fillable = [
        'name',
        'slug',
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
     * Escopo para filtrar segmentos ativos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Escopo para filtrar segmentos inativos
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Boot function from Laravel
     */
    protected static function boot(): void
    {
        parent::boot();

        // Gera o slug automaticamente a partir do nome
        static::creating(function ($segment): void {
            if (empty($segment->slug)) {
                $segment->slug = Str::slug($segment->name);
            }
        });

        static::updating(function ($segment): void {
            if ($segment->isDirty('name') && empty($segment->slug)) {
                $segment->slug = Str::slug($segment->name);
            }
        });

        // Impede a exclusão de segmentos com leads associados
        static::deleting(function ($segment): void {
            if ($segment->hasLeads()) {
                throw new RuntimeException('Não é possível excluir um segmento que possui leads associados');
            }
        });
    }
}
