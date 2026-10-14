<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    protected $table = 'pedidos';

    protected $fillable = [
        'usuario_id',
        'data_pedido',
        'status_pedido',
        'valor_total',
    ];

    protected function casts(): array
    {
        return [
            'data_pedido' => 'datetime',
            'valor_total' => 'decimal:2',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id', 'id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ItemPedido::class, 'pedido_id');
    }
}
