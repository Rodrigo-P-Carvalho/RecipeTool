<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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

    protected function quantidadeNecessariaNaUnidade(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $unidade = $this->ingrediente->unidade_medida;
                $quantidade = $unidade->converterDaBase((float) $this->quantidade_necessaria);

                return rtrim(rtrim(number_format($quantidade, 6, '.', ''), '0'), '.');
            },
        );
    }

    protected function quantidadeNecessariaFormatada(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $unidade = $this->ingrediente->unidade_medida;
                $quantidade = $unidade->converterDaBase((float) $this->quantidade_necessaria);
                $formatada = rtrim(rtrim(number_format($quantidade, 6, ',', '.'), '0'), ',');

                return $formatada.' '.$unidade->value;
            },
        );
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
