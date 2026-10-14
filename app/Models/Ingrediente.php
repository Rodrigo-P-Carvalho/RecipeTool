<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingrediente extends ItemEstoque
{
    protected $table = 'ingredientes';

    protected $fillable = [
        'nome',
        'quantidade_estoque',
        'unidade_medida',
        'estoque_minimo',
        'status_ingrediente',
        'preco_embalagem',
        'tamanho_embalagem',
        'custo_unitario',
    ];

    protected function casts(): array
    {
        return [
            'quantidade_estoque' => 'decimal:3',
            'estoque_minimo' => 'decimal:3',
            'preco_embalagem' => 'decimal:2',
            'tamanho_embalagem' => 'decimal:3',
            'custo_unitario' => 'decimal:4',
        ];
    }

    public function itensReceita(): HasMany
    {
        return $this->hasMany(ItemReceita::class, 'ingrediente_id');
    }
}
