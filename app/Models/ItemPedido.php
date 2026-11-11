<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemPedido extends Model
{
    public $timestamps = false;

    protected $table = 'itens_pedido';

    protected $fillable = [
        'pedido_id',
        'produto_id',
        'qtd',
    ];

    protected function casts(): array
    {
        return [
            'qtd' => 'integer',
        ];
    }

    protected function subtotal(): Attribute
    {
        return Attribute::make(
            get: fn (): string => number_format(
                ((int) round((float) $this->produto->preco_venda * 100) * (int) $this->qtd) / 100,
                2,
                '.',
                '',
            ),
        );
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'pedido_id', 'id');
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'produto_id', 'id');
    }
}
