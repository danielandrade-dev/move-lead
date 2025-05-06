<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Modelo de Segmentos
 *
 * Representa os diferentes segmentos de mercado para categorização de leads.
 * Gerencia a criação automática de slugs e previne exclusão quando há leads associados.
 */
final class Segments extends Model
{
    use HasFactory;

    /**
     * Atributos que são permitidos para atribuição em massa
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    /**
     * Atributos que devem ser convertidos para tipos nativos
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Define o relacionamento com os campos personalizados do segmento
     *
     * Um segmento pode ter múltiplos campos personalizados associados
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany Relacionamento com os campos do segmento
     */
    public function fields()
    {
        return $this->hasMany(SegmentField::class, 'segment_id');
    }

    /**
     * Define o relacionamento com os leads deste segmento
     *
     * Um segmento pode ter múltiplos leads associados
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany Relacionamento com os leads
     */
    public function leads()
    {
        return $this->hasMany(Lead::class, 'segment_id');
    }

    /**
     * Retorna apenas os campos ativos deste segmento
     *
     * Utiliza o escopo 'active' definido no modelo SegmentField
     *
     * @return \Illuminate\Database\Eloquent\Builder Query com os campos ativos
     */
    public function activeFields()
    {
        return $this->fields()->active();
    }

    /**
     * Verifica se o segmento possui leads associados
     *
     * Utilizado para prevenção de exclusão de segmentos em uso
     *
     * @return bool Verdadeiro se o segmento tiver leads associados
     */
    public function hasLeads(): bool
    {
        return $this->leads()->exists();
    }

    /**
     * Escopo para filtrar segmentos ativos
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Query do Eloquent
     * @return \Illuminate\Database\Eloquent\Builder Query modificada
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Escopo para filtrar segmentos inativos
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Query do Eloquent
     * @return \Illuminate\Database\Eloquent\Builder Query modificada
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Método de inicialização do modelo
     *
     * Configura eventos para geração automática de slug e validação na exclusão
     *
     * @return void
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
