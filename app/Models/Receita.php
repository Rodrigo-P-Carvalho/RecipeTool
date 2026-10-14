<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receita extends Model
{
    protected $table = 'receitas';

    protected $fillable = [
        'produto_id',
        'descricao',
        'custo_total',
    ];

    protected function casts(): array
    {
        return [
            'custo_total' => 'decimal:2',
        ];
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'produto_id', 'id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ItemReceita::class, 'receita_id');
    }

    public function producoes(): HasMany
    {
        return $this->hasMany(Producao::class, 'receita_id');
    }

    public function recalcularCusto(): void
    {
        $custoTotal = $this->itens()
            ->join('ingredientes', 'ingredientes.id', '=', 'itens_receita.ingrediente_id')
            ->selectRaw(
                'COALESCE(ROUND(SUM(itens_receita.quantidade_necessaria * ingredientes.custo_unitario), 2), 0) AS custo_total'
            )
            ->value('custo_total');

        $this->forceFill(['custo_total' => $custoTotal])->save();
    }
}
