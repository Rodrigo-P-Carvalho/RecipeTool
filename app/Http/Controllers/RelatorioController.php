<?php

namespace App\Http\Controllers;

use App\Enums\UnidadeMedida;
use App\Services\DashboardMetricsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RelatorioController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'periodo' => ['sometimes', Rule::in(['dia', 'mes_atual', 'mes_anterior', 'ano', 'personalizado'])],
            'de' => ['required_if:periodo,personalizado', 'nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'ate' => [
                'required_if:periodo,personalizado',
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:de',
                'before_or_equal:today',
            ],
        ]);

        $periodoSelecionado = $validated['periodo'] ?? 'mes_atual';
        $hoje = today();

        [$de, $ate] = match ($periodoSelecionado) {
            'dia' => [$hoje->copy(), $hoje->copy()],
            'mes_anterior' => [$hoje->copy()->subMonthNoOverflow()->startOfMonth(), $hoje->copy()->subMonthNoOverflow()->endOfMonth()],
            'ano' => [$hoje->copy()->startOfYear(), $hoje->copy()],
            'personalizado' => [
                Carbon::createFromFormat('Y-m-d', $validated['de']),
                Carbon::createFromFormat('Y-m-d', $validated['ate']),
            ],
            default => [$hoje->copy()->startOfMonth(), $hoje->copy()],
        };

        return view('reports.index', [
            'periodoSelecionado' => $periodoSelecionado,
            'de' => $de->toDateString(),
            'ate' => $ate->toDateString(),
        ]);
    }

    public function executivePdf(DashboardMetricsService $dashboardMetrics): Response
    {
        return Pdf::loadView('reports.pdf.executive', [
            ...$dashboardMetrics->get(),
            'geradoEm' => now(),
        ])
            ->setPaper('a4', 'portrait')
            ->download('relatorio-executivo-ate-'.today()->format('Y-m-d').'.pdf');
    }

    public function production(Request $request): StreamedResponse
    {
        $periodo = $this->validatedPeriod($request);
        $linhas = $this->productionRows($periodo);

        return response()->streamDownload(function () use ($linhas): void {
            $arquivo = fopen('php://output', 'w');
            fputcsv($arquivo, ['Produto', 'Quantidade produzida'], ';');

            foreach ($linhas as $linha) {
                fputcsv($arquivo, [$this->safeCsvText($linha->nome), $linha->quantidade_produzida], ';');
            }

            fclose($arquivo);
        }, $this->reportFilename('relatorio-producao', $periodo, 'csv'), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function productionPdf(Request $request): Response
    {
        $periodo = $this->validatedPeriod($request);

        return Pdf::loadView('reports.pdf.production', [
            'periodo' => $periodo,
            'linhas' => $this->productionRows($periodo),
            'geradoEm' => now(),
        ])
            ->setPaper('a4', 'portrait')
            ->download($this->reportFilename('relatorio-producao', $periodo, 'pdf'));
    }

    public function ingredientConsumption(Request $request): StreamedResponse
    {
        $periodo = $this->validatedPeriod($request);
        $linhas = $this->ingredientConsumptionRows($periodo);

        return response()->streamDownload(function () use ($linhas): void {
            $arquivo = fopen('php://output', 'w');
            fputcsv($arquivo, ['Ingrediente', 'Unidade de medida', 'Quantidade consumida'], ';');

            foreach ($linhas as $linha) {
                fputcsv($arquivo, [
                    $this->safeCsvText($linha->nome),
                    $this->safeCsvText($linha->unidade_medida),
                    $linha->quantidade_consumida,
                ], ';');
            }

            fclose($arquivo);
        }, $this->reportFilename('relatorio-consumo-ingredientes', $periodo, 'csv'), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function ingredientConsumptionPdf(Request $request): Response
    {
        $periodo = $this->validatedPeriod($request);

        return Pdf::loadView('reports.pdf.ingredient-consumption', [
            'periodo' => $periodo,
            'linhas' => $this->ingredientConsumptionRows($periodo),
            'geradoEm' => now(),
        ])
            ->setPaper('a4', 'portrait')
            ->download($this->reportFilename('relatorio-consumo-ingredientes', $periodo, 'pdf'));
    }

    /**
     * @param  array{de: string|null, ate: string|null}  $periodo
     * @return Collection<int, object>
     */
    private function productionRows(array $periodo): Collection
    {
        $query = DB::table('producoes')
            ->join('receitas', 'receitas.id', '=', 'producoes.receita_id')
            ->join('produtos', 'produtos.id', '=', 'receitas.produto_id')
            ->select('produtos.nome', DB::raw('SUM(producoes.quantidade_lotes) AS quantidade_produzida'))
            ->groupBy('produtos.id', 'produtos.nome')
            ->orderByDesc('quantidade_produzida');

        return $this->applyPeriod($query, $periodo)->get();
    }

    /**
     * @param  array{de: string|null, ate: string|null}  $periodo
     * @return Collection<int, object>
     */
    private function ingredientConsumptionRows(array $periodo): Collection
    {
        return $this->applyPeriod(
            DB::table('producoes')
                ->join('itens_receita', 'itens_receita.receita_id', '=', 'producoes.receita_id')
                ->join('ingredientes', 'ingredientes.id', '=', 'itens_receita.ingrediente_id')
                ->select(
                    'ingredientes.nome',
                    'ingredientes.unidade_medida',
                    DB::raw('SUM(itens_receita.quantidade_necessaria * producoes.quantidade_lotes) AS quantidade_consumida'),
                )
                ->groupBy('ingredientes.id', 'ingredientes.nome', 'ingredientes.unidade_medida')
                ->orderBy('ingredientes.nome'),
            $periodo,
        )
            ->get()
            ->map(function (object $linha): object {
                $unidade = UnidadeMedida::from($linha->unidade_medida);

                return (object) [
                    'nome' => $linha->nome,
                    'unidade_medida' => $unidade->value,
                    'quantidade_consumida' => $unidade->converterDaBase((float) $linha->quantidade_consumida),
                ];
            });
    }

    /**
     * @param  array{de: string|null, ate: string|null}  $periodo
     */
    private function applyPeriod(Builder $query, array $periodo): Builder
    {
        if ($periodo['de'] !== null) {
            $query->whereDate('producoes.data_producao', '>=', $periodo['de']);
        }

        if ($periodo['ate'] !== null) {
            $query->whereDate('producoes.data_producao', '<=', $periodo['ate']);
        }

        return $query;
    }

    /**
     * @return array{de: string|null, ate: string|null}
     */
    private function validatedPeriod(Request $request): array
    {
        $validated = $request->validate([
            'de' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'ate' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:de', 'before_or_equal:today'],
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

    /**
     * @param  array{de: string|null, ate: string|null}  $periodo
     */
    private function reportFilename(string $relatorio, array $periodo, string $extensao): string
    {
        $periodoArquivo = match (true) {
            $periodo['de'] !== null && $periodo['ate'] !== null => "{$periodo['de']}-a-{$periodo['ate']}",
            $periodo['de'] !== null => "desde-{$periodo['de']}",
            $periodo['ate'] !== null => "ate-{$periodo['ate']}",
            default => 'periodo-completo',
        };

        return "{$relatorio}-{$periodoArquivo}.{$extensao}";
    }
}
