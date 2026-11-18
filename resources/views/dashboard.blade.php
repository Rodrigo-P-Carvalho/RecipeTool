@extends('layouts.app')

@section('titulo_pagina', 'Painel executivo')

@section('conteudo')
    <div class="space-y-6">
        <section class="flex flex-col gap-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-7">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">RecipeTool · Visão executiva</p>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">Olá, {{ auth()->user()->name }}.</h1>
                <p class="mt-2 text-sm text-slate-500">Indicadores de estoque, rentabilidade, produção e validade em um só lugar.</p>
                <p class="mt-1 text-xs text-slate-400">{{ auth()->user()->email }} · Atualizado em {{ now()->format('d/m/Y H:i') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('purchases.create') }}" class="inline-flex items-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white no-underline transition hover:bg-blue-800">
                    Registrar compra
                </a>
                <a href="{{ route('productions.create') }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 no-underline transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700">
                    Registrar produção
                </a>
                <a href="{{ route('stock-adjustments.create') }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 no-underline transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700">
                    Registrar ajuste manual
                </a>
                <a href="{{ route('reports.index') }}" class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-800 no-underline transition hover:bg-blue-100">
                    Ver relatórios
                </a>
            </div>
        </section>

        <section aria-label="Indicadores principais" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-slate-500">Custo em estoque</p>
                        <p class="mt-3 text-2xl font-semibold tracking-tight text-slate-950">R$ {{ number_format($valorEstoque, 2, ',', '.') }}</p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-sm font-bold text-blue-700" aria-hidden="true">R$</span>
                </div>
                <p class="mt-3 text-xs text-slate-500">Valor estimado dos ingredientes disponíveis.</p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-slate-500">Margem média estimada</p>
                        <p class="mt-3 text-2xl font-semibold tracking-tight {{ $margemMediaPercentual >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ number_format($margemMediaPercentual, 1, ',', '.') }}%</p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-lg font-bold text-emerald-700" aria-hidden="true">%</span>
                </div>
                <p class="mt-3 text-xs text-slate-500">Média da margem bruta por receita com preço de venda.</p>
            </article>

            <a href="{{ route('stock.index') }}" class="rounded-2xl border border-slate-200 bg-white p-5 no-underline shadow-sm transition hover:border-amber-300 hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-slate-500">Alertas de validade</p>
                        <p class="mt-3 text-2xl font-semibold tracking-tight text-amber-700">{{ number_format($quantidadeLotesComAlertas, 0, ',', '.') }}</p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-lg font-bold text-amber-700" aria-hidden="true">!</span>
                </div>
                <p class="mt-3 text-xs text-slate-500">Lotes vencidos ou com validade nos próximos 7 dias.</p>
            </a>

            <a href="{{ route('ingredients') }}" class="rounded-2xl border border-slate-200 bg-white p-5 no-underline shadow-sm transition hover:border-red-300 hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-slate-500">Estoque baixo</p>
                        <p class="mt-3 text-2xl font-semibold tracking-tight text-red-700">{{ number_format($quantidadeEstoqueBaixo, 0, ',', '.') }}</p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-50 text-lg font-bold text-red-700" aria-hidden="true">↓</span>
                </div>
                <p class="mt-3 text-xs text-slate-500">Ingredientes ativos no mínimo ou abaixo dele.</p>
            </a>
        </section>

        <section class="grid gap-6 xl:grid-cols-5">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 xl:col-span-3">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Custo estimado vs. preço de venda</h2>
                        <p class="mt-1 text-sm text-slate-500">Receitas mais produzidas · valores por lote.</p>
                    </div>
                    <a href="{{ route('reports.index') }}" class="text-sm font-semibold text-blue-700 no-underline hover:text-blue-900">Ver relatório</a>
                </div>

                @if ($receitasMaisProduzidas->isNotEmpty())
                    <div class="mt-6 space-y-5">
                        @foreach ($receitasMaisProduzidas as $receita)
                            @php
                                $custo = (float) $receita->custo_total;
                                $preco = (float) $receita->produto->preco_venda;
                                $lotes = (int) $receita->lotes_produzidos;
                            @endphp
                            <div>
                                <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-slate-800">{{ $receita->produto->nome }}</p>
                                    <span class="text-xs text-slate-500">{{ number_format($lotes, 0, ',', '.') }} {{ $lotes === 1 ? 'lote produzido' : 'lotes produzidos' }}</span>
                                </div>
                                <div class="space-y-1.5">
                                    <div class="flex items-center gap-3">
                                        <span class="w-16 shrink-0 text-xs text-slate-500">Custo</span>
                                        <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-slate-100" role="img" aria-label="Custo de produção: R$ {{ number_format($custo, 2, ',', '.') }}">
                                            <div class="h-full rounded-full bg-sky-500" style="width: {{ min(100, $custo / $maiorValorReceita * 100) }}%" title="Custo estimado: R$ {{ number_format($custo, 2, ',', '.') }}"></div>
                                        </div>
                                        <span class="w-20 shrink-0 text-right text-xs font-medium text-slate-700">R$ {{ number_format($custo, 2, ',', '.') }}</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="w-16 shrink-0 text-xs text-slate-500">Venda</span>
                                        <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-slate-100" role="img" aria-label="Preço de venda: R$ {{ number_format($preco, 2, ',', '.') }}">
                                            <div class="h-full rounded-full bg-indigo-600" style="width: {{ min(100, $preco / $maiorValorReceita * 100) }}%" title="Preço de venda: R$ {{ number_format($preco, 2, ',', '.') }}"></div>
                                        </div>
                                        <span class="w-20 shrink-0 text-right text-xs font-medium text-slate-700">R$ {{ number_format($preco, 2, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-5 flex flex-wrap gap-x-5 gap-y-2 border-t border-slate-100 pt-4 text-xs text-slate-500">
                        <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-sky-500"></span>Custo estimado</span>
                        <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-indigo-600"></span>Preço de venda</span>
                    </div>
                @else
                    <p class="mt-6 rounded-xl bg-slate-50 p-4 text-sm text-slate-500">Registre produções para comparar custos e preços das receitas mais produzidas.</p>
                @endif
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 xl:col-span-2">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Composição do custo em estoque</h2>
                    <p class="mt-1 text-sm text-slate-500">Participação do valor por ingrediente.</p>
                </div>

                @if ($distribuicaoGrafico->isNotEmpty())
                    <div class="mt-5 flex flex-col items-center gap-5 sm:flex-row sm:items-center sm:justify-center xl:flex-col 2xl:flex-row">
                        <svg viewBox="0 0 120 120" class="h-40 w-40 shrink-0" role="img" aria-label="Gráfico de rosca com participação do custo em estoque por ingrediente">
                            <circle cx="60" cy="60" r="48" fill="none" stroke="#e2e8f0" stroke-width="16" />
                            @foreach ($distribuicaoGrafico as $item)
                                <circle
                                    cx="60"
                                    cy="60"
                                    r="48"
                                    fill="none"
                                    stroke="{{ $item['cor'] }}"
                                    stroke-width="16"
                                    pathLength="100"
                                    stroke-dasharray="{{ $item['percentual'] }} {{ 100 - $item['percentual'] }}"
                                    stroke-dashoffset="{{ $item['deslocamento'] }}"
                                    transform="rotate(-90 60 60)"
                                >
                                    <title>{{ $item['nome'] }}: R$ {{ number_format($item['valor'], 2, ',', '.') }} ({{ number_format($item['percentual'], 1, ',', '.') }}%)</title>
                                </circle>
                            @endforeach
                            <text x="60" y="56" text-anchor="middle" class="fill-slate-500" font-size="8">Total em estoque</text>
                            <text x="60" y="69" text-anchor="middle" class="fill-slate-900" font-size="10" font-weight="700">R$ {{ number_format($valorEstoque, 2, ',', '.') }}</text>
                        </svg>
                        <ul class="w-full space-y-2.5">
                            @foreach ($distribuicaoGrafico as $item)
                                <li class="flex items-center justify-between gap-3 text-xs" title="{{ $item['nome'] }}: R$ {{ number_format($item['valor'], 2, ',', '.') }}">
                                    <span class="flex min-w-0 items-center gap-2 text-slate-600">
                                        <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $item['cor'] }}"></span>
                                        <span class="truncate">{{ $item['nome'] }}</span>
                                    </span>
                                    <span class="shrink-0 font-semibold text-slate-800">{{ number_format($item['percentual'], 1, ',', '.') }}%</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <p class="mt-4 border-t border-slate-100 pt-3 text-xs text-slate-400">O cadastro atual não possui categorias; a distribuição é apresentada por ingrediente.</p>
                @else
                    <p class="mt-6 rounded-xl bg-slate-50 p-4 text-sm text-slate-500">Não há ingredientes com saldo em estoque para compor a distribuição.</p>
                @endif
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-5">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 xl:col-span-3">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Volume de produção</h2>
                        <p class="mt-1 text-sm text-slate-500">Lotes produzidos por mês · últimos 12 meses.</p>
                    </div>
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">Lotes</span>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <svg viewBox="0 0 640 180" class="min-w-[560px] w-full" role="img" aria-label="Gráfico de linha do volume mensal de produção nos últimos 12 meses">
                        @foreach ([0, 0.3333, 0.6667, 1] as $fracao)
                            @php($y = $alturaGrafico - $margemInferior - $fracao * ($alturaGrafico - 44))
                            <line x1="44" y1="{{ $y }}" x2="624" y2="{{ $y }}" stroke="#e2e8f0" stroke-dasharray="3 4" />
                        @endforeach
                        <path d="{{ $caminhoProducao }}" fill="none" stroke="#2563eb" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                        @foreach ($pontosProducao as $ponto)
                            <circle cx="{{ $ponto['x'] }}" cy="{{ $ponto['y'] }}" r="4" fill="#ffffff" stroke="#2563eb" stroke-width="2">
                                <title>{{ ucfirst($ponto['rotulo']) }}: {{ number_format($ponto['total'], 0, ',', '.') }} {{ $ponto['total'] === 1 ? 'lote' : 'lotes' }}</title>
                            </circle>
                            <text x="{{ $ponto['x'] }}" y="169" text-anchor="middle" class="fill-slate-500" font-size="10">{{ $ponto['rotulo'] }}</text>
                        @endforeach
                    </svg>
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 xl:col-span-2">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Itens que precisam de atenção</h2>
                        <p class="mt-1 text-sm text-slate-500">Prioridade por vencimento e nível de estoque.</p>
                    </div>
                    <a href="{{ route('stock.index') }}" class="shrink-0 text-sm font-semibold text-blue-700 no-underline hover:text-blue-900">Ver estoque</a>
                </div>

                @if ($alertasCriticos !== [])
                    <ul class="mt-4 divide-y divide-slate-100">
                        @foreach ($alertasCriticos as $alerta)
                            <li class="py-3 first:pt-0 last:pb-0">
                                <a href="{{ $alerta['url'] }}" class="block rounded-lg text-sm no-underline focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <span class="flex flex-wrap items-center justify-between gap-2">
                                        <span class="font-semibold text-slate-800">{{ $alerta['nome'] }}</span>
                                        <span class="flex flex-wrap gap-1.5">
                                            @foreach ($alerta['alertas'] as $sinal)
                                                <span @class([
                                                    'rounded-full px-2 py-1 text-[11px] font-semibold',
                                                    'bg-red-50 text-red-700' => $sinal['cor'] === 'red',
                                                    'bg-amber-50 text-amber-800' => $sinal['cor'] === 'amber',
                                                ])>{{ $sinal['rotulo'] }}</span>
                                            @endforeach
                                        </span>
                                    </span>
                                    @foreach ($alerta['alertas'] as $sinal)
                                        <span class="mt-1 block text-xs text-slate-500">{{ $sinal['detalhe'] }}</span>
                                    @endforeach
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="mt-5 rounded-xl border border-emerald-100 bg-emerald-50 p-4">
                        <p class="text-sm font-semibold text-emerald-800">Tudo em ordem</p>
                        <p class="mt-1 text-xs text-emerald-700">Não há ingredientes abaixo do mínimo nem lotes vencidos ou próximos do vencimento.</p>
                    </div>
                @endif
            </article>
        </section>

        <section aria-label="Acesso rápido" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <a href="{{ route('ingredients') }}" class="rounded-xl border border-slate-200 bg-white p-4 text-sm font-semibold text-slate-700 no-underline transition hover:border-blue-300 hover:text-blue-700">Estoque de ingredientes</a>
            <a href="{{ route('products') }}" class="rounded-xl border border-slate-200 bg-white p-4 text-sm font-semibold text-slate-700 no-underline transition hover:border-blue-300 hover:text-blue-700">Estoque de produtos</a>
            <a href="{{ route('recipes') }}" class="rounded-xl border border-slate-200 bg-white p-4 text-sm font-semibold text-slate-700 no-underline transition hover:border-blue-300 hover:text-blue-700">Receitas e custos</a>
            <a href="{{ route('stock-adjustments.index') }}" class="rounded-xl border border-slate-200 bg-white p-4 text-sm font-semibold text-slate-700 no-underline transition hover:border-blue-300 hover:text-blue-700">Movimentações de estoque</a>
        </section>
    </div>
@endsection
