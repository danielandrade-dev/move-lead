<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens;

final class User extends Authenticatable
{
    use HasApiTokens;
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;

    /**
     * Tipos de usuário disponíveis
     */
    public const TYPE_ADMIN = 'admin';
    public const TYPE_MANAGER = 'manager';
    public const TYPE_STORE = 'store';
    public const TYPE_ANALYST = 'analyst';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'type',
        'store_id',
        'company_id',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'store_id' => 'integer',
        'company_id' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Lista de tipos de usuário disponíveis
     */
    public static function getTypesList(): array
    {
        return [
            self::TYPE_ADMIN => 'Administrador',
            self::TYPE_MANAGER => 'Gerente',
            self::TYPE_STORE => 'Loja',
            self::TYPE_ANALYST => 'Analista',
        ];
    }

    /**
     * Relacionamento com a loja
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Relacionamento com a empresa
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Verifica se o usuário é um administrador
     */
    public function isAdmin(): bool
    {
        return $this->type === self::TYPE_ADMIN;
    }

    /**
     * Verifica se o usuário é um gerente
     */
    public function isManager(): bool
    {
        return $this->type === self::TYPE_MANAGER;
    }

    /**
     * Verifica se o usuário é da loja
     */
    public function isStoreUser(): bool
    {
        return $this->type === self::TYPE_STORE;
    }

    /**
     * Verifica se o usuário é um analista
     */
    public function isAnalyst(): bool
    {
        return $this->type === self::TYPE_ANALYST;
    }

    /**
     * Escopo para usuários ativos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Escopo para usuários por tipo
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Boot function from Laravel
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($user): void {
            if (!isset($user->is_active)) {
                $user->is_active = true;
            }
        });
    }
}
