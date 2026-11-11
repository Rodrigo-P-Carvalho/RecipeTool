<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoteIngrediente extends Model
{
    protected $table = 'lotes_ingredientes';

    protected $fillable = [
        'compra_id',
        'ingrediente_id',
        'codigo_lote',
        'quantidade_inicial',
        'quantidade_atual',
        'preco_custo',
        'data_validade',
    ];

    protected function casts(): array
    {
        return [
            'quantidade_inicial' => 'decimal:3',
            'quantidade_atual' => 'decimal:3',
            'preco_custo' => 'decimal:2',
            'data_validade' => 'date',
        ];
    }

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class, 'compra_id');
    }

    public function ingrediente(): BelongsTo
    {
        return $this->belongsTo(Ingrediente::class, 'ingrediente_id');
    }

    public function scopeWithExpiryAlerts(Builder $query): Builder
    {
        return $query
            ->where('quantidade_atual', '>', 0)
            ->whereDate('data_validade', '<=', today()->addDays(7)->toDateString());
    }

    protected function statusValidade(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->data_validade->isBefore(today())
                ? 'Vencido'
                : 'Próximo do vencimento',
        );
    }

    protected function quantidadeAtualFormatada(): Attribute
    {
        return Attribute::make(get: function (): string {
            $ingrediente = $this->ingrediente;
            $quantidade = $ingrediente->unidade_medida->converterDaBase((float) $this->quantidade_atual);
            $formatada = rtrim(rtrim(number_format($quantidade, 2, ',', '.'), '0'), ',');

            return $formatada.' '.$ingrediente->unidade_medida->value;
        });
    }

    protected function quantidadeInicialFormatada(): Attribute
    {
        return Attribute::make(get: function (): string {
            $unidade = $this->ingrediente->unidade_medida;
            $quantidade = $unidade->converterDaBase((float) $this->quantidade_inicial);
            $formatada = rtrim(rtrim(number_format($quantidade, 2, ',', '.'), '0'), ',');

            return $formatada.' '.$unidade->value;
        });
    }
}
