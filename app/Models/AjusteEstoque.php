<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AjusteEstoque extends Model
{
    protected $table = 'ajustes_estoque';

    protected $fillable = [
        'usuario_id',
        'ajustavel_type',
        'ajustavel_id',
        'data_ajuste',
        'tipo_ajuste',
        'quantidade',
        'justificativa',
    ];

    protected function casts(): array
    {
        return [
            'data_ajuste' => 'datetime',
            'quantidade' => 'decimal:3',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function ajustavel(): MorphTo
    {
        return $this->morphTo();
    }
}
