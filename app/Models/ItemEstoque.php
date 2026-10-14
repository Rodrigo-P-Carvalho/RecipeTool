<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Modelo abstrato com os atributos e relacionamentos comuns do estoque.
 */
abstract class ItemEstoque extends Model
{
    protected $fillable = [
        'nome',
        'quantidade_estoque',
    ];

    protected function casts(): array
    {
        return [
            'quantidade_estoque' => 'decimal:3',
        ];
    }

    public function ajustesEstoque(): MorphMany
    {
        return $this->morphMany(AjusteEstoque::class, 'ajustavel');
    }
}
