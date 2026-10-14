<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemReceita extends Model
{
    public $timestamps = false;

    protected $table = 'itens_receita';

    protected $fillable = [
        'receita_id',
        'ingrediente_id',
        'quantidade_necessaria',
    ];

    protected function casts(): array
    {
        return [
            'quantidade_necessaria' => 'decimal:3',
        ];
    }

    public function receita(): BelongsTo
    {
        return $this->belongsTo(Receita::class, 'receita_id', 'id');
    }

    public function ingrediente(): BelongsTo
    {
        return $this->belongsTo(Ingrediente::class, 'ingrediente_id', 'id');
    }
}
