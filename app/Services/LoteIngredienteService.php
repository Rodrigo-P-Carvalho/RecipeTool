<?php

namespace App\Services;

use App\Models\Ingrediente;
use App\Models\LoteIngrediente;
use Illuminate\Database\Eloquent\Builder;

class LoteIngredienteService
{
    public function quantidadeDisponivel(
        Ingrediente $ingrediente,
        bool $somenteLotesValidos = false,
    ): float {
        return $this->lotesDisponiveis($ingrediente, $somenteLotesValidos)
            ->lockForUpdate()
            ->get()
            ->sum(fn (LoteIngrediente $lote): int => (int) round((float) $lote->quantidade_atual * 1000)) / 1000;
    }

    public function consumir(
        Ingrediente $ingrediente,
        float $quantidadeBase,
        bool $somenteLotesValidos = false,
    ): void {
        $quantidadeRestanteEmMiliunidades = (int) round($quantidadeBase * 1000);

        if ($quantidadeRestanteEmMiliunidades <= 0) {
            return;
        }

        $lotes = $this->lotesDisponiveis($ingrediente, $somenteLotesValidos)
            ->lockForUpdate()
            ->get();

        foreach ($lotes as $lote) {
            if ($quantidadeRestanteEmMiliunidades === 0) {
                break;
            }

            $saldoDoLoteEmMiliunidades = (int) round((float) $lote->quantidade_atual * 1000);
            $quantidadeConsumidaEmMiliunidades = min(
                $saldoDoLoteEmMiliunidades,
                $quantidadeRestanteEmMiliunidades,
            );
            $lote->quantidade_atual = number_format(
                ($saldoDoLoteEmMiliunidades - $quantidadeConsumidaEmMiliunidades) / 1000,
                3,
                '.',
                '',
            );
            $lote->save();
            $quantidadeRestanteEmMiliunidades -= $quantidadeConsumidaEmMiliunidades;
        }
    }

    private function lotesDisponiveis(Ingrediente $ingrediente, bool $somenteLotesValidos): Builder
    {
        $query = LoteIngrediente::query()
            ->where('ingrediente_id', $ingrediente->id)
            ->where('quantidade_atual', '>', 0)
            ->orderBy('data_validade')
            ->orderBy('id');

        if ($somenteLotesValidos) {
            $query->whereDate('data_validade', '>=', today()->toDateString());
        }

        return $query;
    }
}
