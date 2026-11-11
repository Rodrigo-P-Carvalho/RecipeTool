<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Produto extends ItemEstoque
{
    protected $table = 'produtos';

    protected $fillable = [
        'nome',
        'quantidade_estoque',
        'descricao',
        'preco_venda',
    ];

    protected function casts(): array
    {
        return [
            'quantidade_estoque' => 'decimal:3',
            'preco_venda' => 'decimal:2',
        ];
    }

    protected function quantidadeEstoqueFormatada(): Attribute
    {
        return Attribute::make(get: function (): string {
            $quantidade = number_format((float) $this->quantidade_estoque, 3, ',', '.');

            return rtrim(rtrim($quantidade, '0'), ',');
        });
    }

    public function receita(): HasOne
    {
        return $this->hasOne(Receita::class, 'produto_id');
    }

    public function itensPedido(): HasMany
    {
        return $this->hasMany(ItemPedido::class, 'produto_id');
    }
}
