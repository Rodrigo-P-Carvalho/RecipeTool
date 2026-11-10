<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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

    protected function quantidadeFormatada(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $quantidade = (float) $this->quantidade;
                $ajustavel = $this->ajustavel;

                if ($ajustavel instanceof Ingrediente) {
                    $unidade = $ajustavel->unidade_medida;
                    $quantidade = $unidade->converterDaBase($quantidade);
                    $formatada = rtrim(rtrim(number_format($quantidade, 3, ',', '.'), '0'), ',');

                    return $formatada.' '.$unidade->value;
                }

                return rtrim(rtrim(number_format($quantidade, 3, ',', '.'), '0'), ',');
            },
        );
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
