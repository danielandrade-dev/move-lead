<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LeadPhone extends Model
{
    use HasFactory;

    /**
     * Atributos que são permitidos para atribuição em massa
     */
    protected $fillable = [
        'lead_id',
        'phone_original',
        'phone_normalized',
        'is_active',
    ];

    /**
     * Atributos que devem ser convertidos para tipos nativos
     */
    protected $casts = [
        'lead_id' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Normaliza o número de telefone (remove tudo exceto números)
    public static function normalizePhone(string $phone): string
    {
        // Remove todos os caracteres não numéricos
        $normalized = preg_replace('/[^0-9]/', '', $phone);

        // Remove o código do país se existir (assumindo Brasil - 55)
        if (str_starts_with($normalized, '55') && strlen($normalized) > 11) {
            $normalized = substr($normalized, 2);
        }

        // Remove o 9 inicial de celulares se o número tiver mais de 9 dígitos
        if (strlen($normalized) > 9 && str_starts_with($normalized, '9')) {
            $normalized = substr($normalized, 1);
        }

        return $normalized;
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Verifica se dois números de telefone são equivalentes após normalização
     */
    public static function areEquivalent(string $phone1, string $phone2): bool
    {
        return self::normalizePhone($phone1) === self::normalizePhone($phone2);
    }

    /**
     * Boot function from Laravel
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
