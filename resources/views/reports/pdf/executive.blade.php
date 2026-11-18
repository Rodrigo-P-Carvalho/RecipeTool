<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Relatório executivo · RecipeTool</title>
    <style>
        @page { margin: 28px 32px; }
        body { color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h1 { color: #0f172a; font-size: 23px; margin: 6px 0; }
        h2 { color: #1e293b; font-size: 13px; margin: 0 0 10px; }
        h3 { color: #334155; font-size: 11px; margin: 15px 0 7px; }
        .brand { color: #1d4ed8; font-size: 9px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        .meta { color: #64748b; margin: 0 0 17px; }
        .kpis { border-collapse: separate; border-spacing: 6px; margin: 0 -6px 10px; width: 100%; }
        .kpis td { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; vertical-align: top; width: 25%; }
        .label { color: #64748b; font-size: 9px; }
        .value { color: #0f172a; font-size: 16px; font-weight: bold; margin-top: 5px; }
        .section { margin-top: 15px; }
        table.data { border-collapse: collapse; width: 100%; }
        .data th { background: #eff6ff; color: #1e3a8a; font-size: 9px; text-align: left; }
        .data th, .data td { border-bottom: 1px solid #e2e8f0; padding: 6px 7px; }
        .data .number { text-align: right; }
        .bar-bg { background: #e2e8f0; height: 7px; width: 100%; }
        .bar-blue { background: #2563eb; height: 7px; }
        .bar-teal { background: #0d9488; height: 7px; }
        .muted { color: #64748b; }
        .alert { border-bottom: 1px solid #e2e8f0; padding: 6px 2px; }
        .alert-name { font-weight: bold; }
        .footer { border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 8px; margin-top: 20px; padding-top: 7px; }
    </style>
</head>
<body>
    <p class="brand">RecipeTool · Visão executiva</p>
    <h1>Resumo gerencial</h1>
    <p class="meta">Estoque, rentabilidade, produção e alertas · Gerado em {{ $geradoEm->format('d/m/Y H:i') }}</p>

    <table class="kpis">
        <tr>
            <td><div class="label">Custo estimado em estoque</div><div class="value">R$ {{ number_format($valorEstoque, 2, ',', '.') }}</div></td>
            <td><div class="label">Margem média estimada</div><div class="value">{{ number_format($margemMediaPercentual, 1, ',', '.') }}%</div></td>
            <td><div class="label">Lotes com alerta de validade</div><div class="value">{{ number_format($quantidadeLotesComAlertas, 0, ',', '.') }}</div></td>
            <td><div class="label">Ingredientes com estoque baixo</div><div class="value">{{ number_format($quantidadeEstoqueBaixo, 0, ',', '.') }}</div></td>
        </tr>
    </table>

    <section class="section">
        <h2>Custo estimado vs. preço de venda</h2>
        @if ($receitasMaisProduzidas->isNotEmpty())
            <table class="data">
                <thead><tr><th>Produto</th><th>Custo por lote</th><th>Preço de venda</th><th>Lotes produzidos</th></tr></thead>
                <tbody>
                    @foreach ($receitasMaisProduzidas as $receita)
                        @php
                            $custo = (float) $receita->custo_total;
                            $preco = (float) $receita->produto->preco_venda;
                        @endphp
                        <tr>
                            <td>{{ $receita->produto->nome }}</td>
                            <td>R$ {{ number_format($custo, 2, ',', '.') }}</td>
                            <td>R$ {{ number_format($preco, 2, ',', '.') }}</td>
                            <td class="number">{{ number_format((float) $receita->lotes_produzidos, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td colspan="4">
                                <div class="bar-bg"><div class="bar-blue" style="width: {{ min(100, $custo / $maiorValorReceita * 100) }}%"></div></div>
                                <div class="bar-bg" style="margin-top: 3px"><div class="bar-teal" style="width: {{ min(100, $preco / $maiorValorReceita * 100) }}%"></div></div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="muted">Barras: custo de produção (azul) e preço de venda (verde-azulado).</p>
        @else
            <p class="muted">Registre produções para comparar custos e preços das receitas mais produzidas.</p>
        @endif
    </section>

    <section class="section">
        <h2>Distribuição do custo em estoque</h2>
        @if ($distribuicaoGrafico->isNotEmpty())
            <table class="data">
                <thead><tr><th>Ingrediente</th><th>Participação</th><th class="number">Valor</th></tr></thead>
                <tbody>
                    @foreach ($distribuicaoGrafico as $item)
                        <tr>
                            <td>{{ $item['nome'] }}</td>
                            <td>
                                <div class="bar-bg"><div class="bar-blue" style="width: {{ min(100, $item['percentual']) }}%"></div></div>
                                <span class="muted">{{ number_format($item['percentual'], 1, ',', '.') }}%</span>
                            </td>
                            <td class="number">R$ {{ number_format($item['valor'], 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="muted">Não há ingredientes com saldo em estoque para compor a distribuição.</p>
        @endif
    </section>

    <section class="section">
        <h2>Volume mensal de produção</h2>
        <table class="data">
            <thead><tr><th>Mês</th><th>Produção (lotes)</th><th></th></tr></thead>
            <tbody>
                @foreach ($mesesProducao as $mes)
                    <tr>
                        <td>{{ ucfirst($mes['rotulo']) }}</td>
                        <td>{{ number_format($mes['total'], 0, ',', '.') }}</td>
                        <td><div class="bar-bg"><div class="bar-teal" style="width: {{ min(100, $mes['total'] / max(1, $mesesProducao->max('total')) * 100) }}%"></div></div></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <section class="section">
        <h2>Itens que precisam de atenção</h2>
        @forelse ($alertasCriticos as $alerta)
            <div class="alert">
                <span class="alert-name">{{ $alerta['nome'] }}</span>
                @foreach ($alerta['alertas'] as $sinal)
                    <span> · {{ $sinal['rotulo'] }}: {{ $sinal['detalhe'] }}</span>
                @endforeach
            </div>
        @empty
            <p class="muted">Não há ingredientes abaixo do mínimo nem lotes vencidos ou próximos do vencimento.</p>
        @endforelse
    </section>

    <p class="footer">Indicadores de margem, custo e consumo são estimativas baseadas nos dados cadastrados no RecipeTool.</p>
</body>
</html>
