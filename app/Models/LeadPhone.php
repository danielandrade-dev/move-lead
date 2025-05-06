<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Telefone de Lead
 *
 * Representa os números de telefone associados a um lead.
 * Gerencia a normalização de telefones para garantir consistência
 * e prevenir duplicações na identificação de leads.
 */
final class LeadPhone extends Model
{
    use HasFactory;

    /**
     * Atributos que são permitidos para atribuição em massa
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'lead_id',
        'phone_original',
        'phone_normalized',
        'is_active',
    ];

    /**
     * Atributos que devem ser convertidos para tipos nativos
     *
     * @var array<string, string>
     */
    protected $casts = [
        'lead_id' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Normaliza o número de telefone para formato padrão
     *
     * Remove caracteres não numéricos, código do país (55) e o 9 inicial
     * de celulares, mantendo apenas os dígitos essenciais para comparação
     *
     * @param string $phone Número de telefone original
     * @return string Número de telefone normalizado
     */
    public static function normalizePhone(string $phone): string
    {
        // Remove todos os caracteres não numéricos
        $normalized = preg_replace('/[^0-9]/', '', $phone);

        // Remove o código do país se existir (assumindo Brasil - 55)
        if (str_starts_with($normalized, '55') && mb_strlen($normalized) > 11) {
            $normalized = mb_substr($normalized, 2);
        }

        // Remove o 9 inicial de celulares se o número tiver mais de 9 dígitos
        if (mb_strlen($normalized) > 9 && str_starts_with($normalized, '9')) {
            $normalized = mb_substr($normalized, 1);
        }

        return $normalized;
    }

    /**
     * Verifica se dois números de telefone são equivalentes após normalização
     *
     * Útil para comparar telefones em diferentes formatos
     *
     * @param string $phone1 Primeiro número de telefone
     * @param string $phone2 Segundo número de telefone
     * @return bool Verdadeiro se os números forem equivalentes após normalização
     */
    public static function areEquivalent(string $phone1, string $phone2): bool
    {
        return self::normalizePhone($phone1) === self::normalizePhone($phone2);
    }

    /**
     * Define o relacionamento com o lead
     *
     * Um telefone pertence a um único lead
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relacionamento com o lead
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Método de inicialização do modelo
     *
     * Configura eventos para normalização automática de telefones
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($phone): void {
            if (empty($phone->phone_normalized)) {
                $phone->phone_normalized = self::normalizePhone($phone->phone_original);
            }
        });
    }
}
