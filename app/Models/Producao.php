<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Producao extends Model
{
    protected $table = 'producoes';

    protected $fillable = [
        'usuario_id',
        'receita_id',
        'quantidade_lotes',
        'data_producao',
    ];

    protected function casts(): array
    {
        return [
            'quantidade_lotes' => 'integer',
            'data_producao' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id', 'id');
    }

    public function receita(): BelongsTo
    {
        return $this->belongsTo(Receita::class, 'receita_id', 'id');
    }
}
