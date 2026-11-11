<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Compra extends Model
{
    protected $fillable = [
        'usuario_id',
        'data_compra',
        'valor_total',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'data_compra' => 'datetime',
            'valor_total' => 'decimal:2',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function lotes(): HasMany
    {
        return $this->hasMany(LoteIngrediente::class, 'compra_id');
    }
}
