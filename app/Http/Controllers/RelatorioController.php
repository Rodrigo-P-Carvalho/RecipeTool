<?php

namespace App\Http\Controllers;

use App\Enums\UnidadeMedida;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RelatorioController extends Controller
{
    public function production(Request $request): StreamedResponse
    {
        $periodo = $this->validatedPeriod($request);
        $query = DB::table('producoes')
            ->join('receitas', 'receitas.id', '=', 'producoes.receita_id')
            ->join('produtos', 'produtos.id', '=', 'receitas.produto_id')
            ->select('produtos.nome', DB::raw('SUM(producoes.quantidade_lotes) AS quantidade_produzida'))
            ->groupBy('produtos.id', 'produtos.nome')
            ->orderByDesc('quantidade_produzida');

        if ($periodo['de'] !== null) {
            $query->whereDate('producoes.data_producao', '>=', $periodo['de']);
        }

        if ($periodo['ate'] !== null) {
            $query->whereDate('producoes.data_producao', '<=', $periodo['ate']);
        }

        $linhas = $query->get();

        return response()->streamDownload(function () use ($linhas): void {
            $arquivo = fopen('php://output', 'w');
            fputcsv($arquivo, ['Produto', 'Quantidade produzida'], ';');

            foreach ($linhas as $linha) {
                fputcsv($arquivo, [$this->safeCsvText($linha->nome), $linha->quantidade_produzida], ';');
            }

            fclose($arquivo);
        }, 'relatorio-producao.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function ingredientConsumption(Request $request): StreamedResponse
    {
        $periodo = $this->validatedPeriod($request);
        $query = DB::table('producoes')
            ->join('itens_receita', 'itens_receita.receita_id', '=', 'producoes.receita_id')
            ->join('ingredientes', 'ingredientes.id', '=', 'itens_receita.ingrediente_id')
            ->select(
                'ingredientes.nome',
                'ingredientes.unidade_medida',
                DB::raw('SUM(itens_receita.quantidade_necessaria * producoes.quantidade_lotes) AS quantidade_consumida'),
            )
            ->groupBy('ingredientes.id', 'ingredientes.nome', 'ingredientes.unidade_medida')
            ->orderBy('ingredientes.nome');

        if ($periodo['de'] !== null) {
            $query->whereDate('producoes.data_producao', '>=', $periodo['de']);
        }

        if ($periodo['ate'] !== null) {
            $query->whereDate('producoes.data_producao', '<=', $periodo['ate']);
        }

        $linhas = $query->get();

        return response()->streamDownload(function () use ($linhas): void {
            $arquivo = fopen('php://output', 'w');
            fputcsv($arquivo, ['Ingrediente', 'Unidade de medida', 'Quantidade consumida'], ';');

            foreach ($linhas as $linha) {
                $unidade = UnidadeMedida::from($linha->unidade_medida);
                fputcsv($arquivo, [
                    $this->safeCsvText($linha->nome),
                    $this->safeCsvText($unidade->value),
                    $unidade->converterDaBase((float) $linha->quantidade_consumida),
                ], ';');
            }

            fclose($arquivo);
        }, 'relatorio-consumo-ingredientes.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{de: string|null, ate: string|null}
     */
    private function validatedPeriod(Request $request): array
    {
        $validated = $request->validate([
            'de' => ['nullable', 'date'],
            'ate' => ['nullable', 'date', 'after_or_equal:de'],
        ]);

        return [
            'de' => $validated['de'] ?? null,
            'ate' => $validated['ate'] ?? null,
        ];
    }

    private function safeCsvText(string $value): string
    {
        return preg_match('/^[\s]*[=+\-@]/u', $value) === 1
            ? "'{$value}"
            : $value;
    }
}
