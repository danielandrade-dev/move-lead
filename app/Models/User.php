<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Modelo de Usuário do sistema
 *
 * Representa os usuários da aplicação com diferentes níveis de acesso
 * e funcionalidades específicas para cada tipo de usuário.
 */
final class User extends Authenticatable
{
    use HasApiTokens;
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;

    /**
     * Tipos de usuário disponíveis
     *
     * Constantes que definem os possíveis tipos de usuário no sistema
     */
    public const TYPE_ADMIN = 'admin';
    public const TYPE_MANAGER = 'manager';
    public const TYPE_STORE = 'store';
    public const TYPE_ANALYST = 'analyst';

    /**
     * Atributos que são permitidos para atribuição em massa
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
     * Atributos que devem ser ocultos na serialização
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Atributos que devem ser convertidos para tipos nativos
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
     * Retorna a lista de tipos de usuário disponíveis com seus rótulos
     *
     * @return array<string, string> Matriz associativa com os tipos de usuário e seus respectivos nomes legíveis
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
     * Define o relacionamento entre usuário e loja
     *
     * Um usuário pode estar associado a uma loja específica
     *
     * @return BelongsTo Relacionamento de pertencimento à loja
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Define o relacionamento entre usuário e empresa
     *
     * Um usuário pode estar associado a uma empresa específica
     *
     * @return BelongsTo Relacionamento de pertencimento à empresa
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Verifica se o usuário é um administrador
     *
     * @return bool Verdadeiro se o usuário for um administrador, falso caso contrário
     */
    public function isAdmin(): bool
    {
        return self::TYPE_ADMIN === $this->type;
    }

    /**
     * Verifica se o usuário é um gerente
     *
     * @return bool Verdadeiro se o usuário for um gerente, falso caso contrário
     */
    public function isManager(): bool
    {
        return self::TYPE_MANAGER === $this->type;
    }

    /**
     * Verifica se o usuário é da loja
     *
     * @return bool Verdadeiro se o usuário for da loja, falso caso contrário
     */
    public function isStoreUser(): bool
    {
        return self::TYPE_STORE === $this->type;
    }

    /**
     * Verifica se o usuário é um analista
     *
     * @return bool Verdadeiro se o usuário for um analista, falso caso contrário
     */
    public function isAnalyst(): bool
    {
        return self::TYPE_ANALYST === $this->type;
    }

    /**
     * Escopo para filtrar usuários ativos
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Query do Eloquent
     * @return \Illuminate\Database\Eloquent\Builder Query modificada
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Escopo para filtrar usuários por tipo
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Query do Eloquent
     * @param string $type Tipo de usuário a ser filtrado
     * @return \Illuminate\Database\Eloquent\Builder Query modificada
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Método de inicialização do modelo
     *
     * Configura eventos para garantir valores padrão durante a criação
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($user): void {
            if ( ! isset($user->is_active)) {
                $user->is_active = true;
            }
        });
    }
}
