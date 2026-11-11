<?php

namespace App\Services;

use App\Models\Ingrediente;
use App\Models\LoteIngrediente;

class LoteIngredienteService
{
    public function consumir(Ingrediente $ingrediente, float $quantidadeBase): void
    {
        $quantidadeRestanteEmMiliunidades = (int) round($quantidadeBase * 1000);

        if ($quantidadeRestanteEmMiliunidades <= 0) {
            return;
        }

        $lotes = LoteIngrediente::query()
            ->where('ingrediente_id', $ingrediente->id)
            ->where('quantidade_atual', '>', 0)
            ->orderBy('data_validade')
            ->orderBy('id')
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
}
