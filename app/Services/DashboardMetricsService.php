<?php

namespace App\Services;

use App\Models\Ingrediente;
use App\Models\LoteIngrediente;
use App\Models\Producao;
use App\Models\Receita;
use Illuminate\Support\Facades\DB;

class DashboardMetricsService
{
    /**
     * @return array<string, mixed>
     */
    public function get(): array
    {
        $valorEstoque = (float) Ingrediente::query()
            ->selectRaw('COALESCE(SUM(quantidade_estoque * custo_unitario), 0) AS total')
            ->value('total');

        $ingredientesComEstoqueBaixo = Ingrediente::query()
            ->where('status_ingrediente', 'ativo')
            ->whereColumn('quantidade_estoque', '<=', 'estoque_minimo');
        $quantidadeEstoqueBaixo = (clone $ingredientesComEstoqueBaixo)->count();

        $lotesComAlertas = LoteIngrediente::query()
            ->where('quantidade_atual', '>', 0)
            ->where('data_validade', '<=', today()->addDays(7)->toDateString());
        $quantidadeLotesComAlertas = (clone $lotesComAlertas)->count();

        $alertasCriticos = [];

        foreach (
            (clone $ingredientesComEstoqueBaixo)
                ->orderByRaw('CASE WHEN estoque_minimo > 0 THEN quantidade_estoque / estoque_minimo ELSE 1 END')
                ->limit(8)
                ->get(['id', 'nome', 'quantidade_estoque', 'estoque_minimo', 'unidade_medida']) as $ingrediente
        ) {
            $alertasCriticos[$ingrediente->id] = [
                'nome' => $ingrediente->nome,
                'url' => route('ingredients.show', $ingrediente),
                'prioridade' => 2,
                'alertas' => [[
                    'rotulo' => 'Estoque baixo',
                    'detalhe' => "{$ingrediente->quantidade_estoque_formatada} disponível · mínimo {$ingrediente->estoque_minimo_formatado}",
                    'cor' => 'amber',
                ]],
            ];
        }

        $lotesParaAlerta = (clone $lotesComAlertas)
            ->with('ingrediente:id,nome')
            ->orderBy('data_validade')
            ->orderBy('id')
            ->limit(30)
            ->get(['id', 'ingrediente_id', 'codigo_lote', 'quantidade_atual', 'data_validade']);

        foreach ($lotesParaAlerta as $lote) {
            $vencido = $lote->data_validade->isBefore(today());
            $ingredienteId = $lote->ingrediente_id;
            $alertasCriticos[$ingredienteId] ??= [
                'nome' => $lote->ingrediente->nome,
                'url' => route('ingredients.show', $lote->ingrediente),
                'prioridade' => $vencido ? 0 : 1,
                'alertas' => [],
            ];

            $alertasCriticos[$ingredienteId]['prioridade'] = min(
                $alertasCriticos[$ingredienteId]['prioridade'],
                $vencido ? 0 : 1,
            );

            if (collect($alertasCriticos[$ingredienteId]['alertas'])->contains(
                fn (array $alerta): bool => in_array($alerta['rotulo'], ['Vencido', 'Vence em até 7 dias'], true),
            )) {
                continue;
            }

            $codigoLote = $lote->codigo_lote ?: "Lote #{$lote->id}";
            $alertasCriticos[$ingredienteId]['alertas'][] = [
                'rotulo' => $vencido ? 'Vencido' : 'Vence em até 7 dias',
                'detalhe' => "{$codigoLote} · {$lote->data_validade->format('d/m/Y')}",
                'cor' => $vencido ? 'red' : 'amber',
            ];
        }

        usort(
            $alertasCriticos,
            fn (array $a, array $b): int => $a['prioridade'] <=> $b['prioridade']
                ?: strcasecmp($a['nome'], $b['nome']),
        );

        $margemMediaPercentual = (float) (Receita::query()
            ->join('produtos', 'produtos.id', '=', 'receitas.produto_id')
            ->where('produtos.preco_venda', '>', 0)
            ->selectRaw(
                'AVG(100.0 * (produtos.preco_venda - receitas.custo_total) / produtos.preco_venda) AS margem_media'
            )
            ->value('margem_media') ?? 0);

        $receitasMaisProduzidas = Receita::query()
            ->join('producoes', 'producoes.receita_id', '=', 'receitas.id')
            ->join('produtos', 'produtos.id', '=', 'receitas.produto_id')
            ->with('produto:id,nome,preco_venda')
            ->select([
                'receitas.id',
                'receitas.produto_id',
                'receitas.custo_total',
                'produtos.nome',
                'produtos.preco_venda',
            ])
            ->selectRaw('SUM(producoes.quantidade_lotes) AS lotes_produzidos')
            ->groupBy(
                'receitas.id',
                'receitas.produto_id',
                'receitas.custo_total',
                'produtos.id',
                'produtos.nome',
                'produtos.preco_venda',
            )
            ->orderByDesc('lotes_produzidos')
            ->orderBy('produtos.nome')
            ->limit(6)
            ->get();
        $valoresReceitas = $receitasMaisProduzidas
            ->map(fn (Receita $receita): float => max(
                (float) $receita->custo_total,
                (float) $receita->produto->preco_venda,
            ))
            ->all();
        $maiorValorReceita = max($valoresReceitas ?: [1]);

        $distribuicaoCustos = Ingrediente::query()
            ->where('quantidade_estoque', '>', 0)
            ->select('ingredientes.id', 'ingredientes.nome')
            ->selectRaw('SUM(ingredientes.quantidade_estoque * ingredientes.custo_unitario) AS valor')
            ->groupBy('ingredientes.id', 'ingredientes.nome')
            ->orderByDesc('valor')
            ->limit(5)
            ->get();
        $coresGrafico = ['#2563eb', '#0d9488', '#8b5cf6', '#f59e0b', '#ec4899', '#94a3b8'];
        $distribuicaoGrafico = $distribuicaoCustos
            ->map(fn (Ingrediente $ingrediente, int $indice): array => [
                'nome' => $ingrediente->nome,
                'valor' => (float) $ingrediente->valor,
                'cor' => $coresGrafico[$indice],
            ])
            ->values();
        $valorTopIngredientes = $distribuicaoGrafico->sum('valor');
        $valorOutrosIngredientes = max(0, $valorEstoque - $valorTopIngredientes);

        if ($valorOutrosIngredientes > 0.01) {
            $distribuicaoGrafico->push([
                'nome' => 'Outros ingredientes',
                'valor' => $valorOutrosIngredientes,
                'cor' => $coresGrafico[5],
            ]);
        }

        $percentualAcumulado = 0;
        $distribuicaoGrafico = $distribuicaoGrafico
            ->map(function (array $item) use (&$percentualAcumulado, $valorEstoque): array {
                $item['percentual'] = $valorEstoque > 0
                    ? $item['valor'] / $valorEstoque * 100
                    : 0;
                $item['deslocamento'] = -$percentualAcumulado;
                $percentualAcumulado += $item['percentual'];

                return $item;
            })
            ->values();

        $inicioPeriodo = today()->startOfMonth()->subMonths(11);
        $fimPeriodo = today()->startOfMonth()->addMonth();
        $driver = DB::connection()->getDriverName();
        $expressaoMes = match ($driver) {
            'pgsql' => "to_char(data_producao, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', data_producao)",
            default => "DATE_FORMAT(data_producao, '%Y-%m')",
        };
        $producoesPorMes = Producao::query()
            ->where('data_producao', '>=', $inicioPeriodo)
            ->where('data_producao', '<', $fimPeriodo)
            ->selectRaw("{$expressaoMes} AS periodo")
            ->selectRaw('SUM(quantidade_lotes) AS total')
            ->groupByRaw($expressaoMes)
            ->pluck('total', 'periodo');

        $nomesMeses = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
        $mesesProducao = collect(range(0, 11))
            ->map(fn (int $indice): array => [
                'periodo' => $inicioPeriodo->copy()->addMonths($indice)->format('Y-m'),
                'rotulo' => $nomesMeses[(int) $inicioPeriodo->copy()->addMonths($indice)->format('n') - 1],
                'total' => (int) $producoesPorMes->get($inicioPeriodo->copy()->addMonths($indice)->format('Y-m'), 0),
            ]);
        $maiorVolumeMensal = max(1, ...$mesesProducao->pluck('total')->all());
        $larguraGrafico = 640;
        $alturaGrafico = 180;
        $margemEsquerda = 44;
        $margemDireita = 16;
        $margemSuperior = 12;
        $margemInferior = 32;
        $pontosProducao = $mesesProducao
            ->map(function (array $mes, int $indice) use (
                $maiorVolumeMensal,
                $larguraGrafico,
                $alturaGrafico,
                $margemEsquerda,
                $margemDireita,
                $margemSuperior,
                $margemInferior,
            ): array {
                $x = $margemEsquerda + $indice * (
                    ($larguraGrafico - $margemEsquerda - $margemDireita) / 11
                );
                $y = $alturaGrafico - $margemInferior
                    - ($mes['total'] / $maiorVolumeMensal)
                    * ($alturaGrafico - $margemSuperior - $margemInferior);

                return $mes + ['x' => round($x, 2), 'y' => round($y, 2)];
            });
        $caminhoProducao = $pontosProducao
            ->map(fn (array $ponto, int $indice): string => ($indice === 0 ? 'M' : 'L')
                ." {$ponto['x']} {$ponto['y']}")
            ->implode(' ');

        return [
            'valorEstoque' => $valorEstoque,
            'margemMediaPercentual' => $margemMediaPercentual,
            'quantidadeLotesComAlertas' => $quantidadeLotesComAlertas,
            'quantidadeEstoqueBaixo' => $quantidadeEstoqueBaixo,
            'receitasMaisProduzidas' => $receitasMaisProduzidas,
            'maiorValorReceita' => $maiorValorReceita,
            'distribuicaoGrafico' => $distribuicaoGrafico,
            'alertasCriticos' => array_slice($alertasCriticos, 0, 8),
            'mesesProducao' => $mesesProducao,
            'pontosProducao' => $pontosProducao,
            'caminhoProducao' => $caminhoProducao,
            'alturaGrafico' => $alturaGrafico,
            'margemInferior' => $margemInferior,
        ];
    }
}
