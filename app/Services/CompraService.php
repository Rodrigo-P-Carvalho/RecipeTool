<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\Ingrediente;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CompraService
{
    /**
     * @param  array{
     *     data_compra: string,
     *     observacoes?: ?string,
     *     itens: array<int, array{
     *         ingrediente_id: int|string,
     *         quantidade: int|float|string,
     *         preco_custo: int|float|string,
     *         data_validade: string,
     *         codigo_lote?: ?string
     *     }>
     * }  $dados
     */
    public function registrarCompra(array $dados, int $usuarioId): Compra
    {
        return DB::transaction(function () use ($dados, $usuarioId): Compra {
            $ingredienteIds = collect($dados['itens'])
                ->pluck('ingrediente_id')
                ->map(fn (int|string $id): int => (int) $id)
                ->unique()
                ->sort()
                ->values();
            $ingredientes = Ingrediente::query()
                ->whereIn('id', $ingredienteIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $dataCompra = Carbon::parse($dados['data_compra']);
            $valorTotalCentavos = collect($dados['itens'])
                ->sum(fn (array $item): int => (int) round((float) $item['preco_custo'] * 100));

            $compra = Compra::query()->create([
                'usuario_id' => $usuarioId,
                'data_compra' => $dataCompra,
                'valor_total' => number_format($valorTotalCentavos / 100, 2, '.', ''),
                'observacoes' => $dados['observacoes'] ?? null,
            ]);

            foreach ($dados['itens'] as $indice => $item) {
                $ingredienteId = (int) $item['ingrediente_id'];
                $ingrediente = $ingredientes->get($ingredienteId);

                if (! $ingrediente instanceof Ingrediente) {
                    throw ValidationException::withMessages([
                        "itens.{$indice}.ingrediente_id" => 'O ingrediente selecionado não está mais disponível.',
                    ]);
                }

                $unidade = $ingrediente->unidade_medida;
                $quantidadeBase = $unidade->converterParaBase((float) $item['quantidade']);

                if ($quantidadeBase <= 0) {
                    throw ValidationException::withMessages([
                        "itens.{$indice}.quantidade" => 'A quantidade informada é menor que a menor unidade armazenável.',
                    ]);
                }

                $lote = $compra->lotes()->create([
                    'ingrediente_id' => $ingrediente->id,
                    'codigo_lote' => ($item['codigo_lote'] ?? null) ?: 'LOTE-'.Str::upper(Str::random(10)),
                    'quantidade_inicial' => number_format($quantidadeBase, 3, '.', ''),
                    'quantidade_atual' => number_format($quantidadeBase, 3, '.', ''),
                    'preco_custo' => number_format((float) $item['preco_custo'], 2, '.', ''),
                    'data_validade' => $item['data_validade'],
                ]);
                $ingrediente->increment('quantidade_estoque', $quantidadeBase);
                $ingrediente->ajustesEstoque()->create([
                    'usuario_id' => $usuarioId,
                    'data_ajuste' => $dataCompra,
                    'tipo_ajuste' => 'compra_entrada',
                    'quantidade' => number_format($quantidadeBase, 3, '.', ''),
                    'justificativa' => "Entrada da compra {$compra->id}, lote {$lote->codigo_lote}.",
                ]);
            }

            return $compra;
        }, attempts: 3);
    }
}
