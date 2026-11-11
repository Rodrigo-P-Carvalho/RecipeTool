<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    protected $attributes = [
        'role' => 'funcionario',
        'precisa_trocar_senha' => false,
    ];

    public const ROLES = [
        'admin' => 'Administrador',
        'gerente' => 'Gerente',
        'funcionario' => 'Funcionário',
    ];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'precisa_trocar_senha' => 'boolean',
        ];
    }

    public function producoes(): HasMany
    {
        return $this->hasMany(Producao::class, 'usuario_id');
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'usuario_id');
    }

    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class, 'usuario_id');
    }

    public function ajustesEstoque(): HasMany
    {
        return $this->hasMany(AjusteEstoque::class, 'usuario_id');
    }
}
