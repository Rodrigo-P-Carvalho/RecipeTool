@extends('layouts.app')

@section('titulo_pagina', 'Relatórios')

@section('conteudo')
    @php
        $periodosRapidos = [
            'dia' => 'Hoje',
            'mes_atual' => 'Este mês',
            'mes_anterior' => 'Mês passado',
            'ano' => 'Este ano',
        ];
        $filtrosPeriodo = ['de' => $de, 'ate' => $ate];
    @endphp

    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Análises · RecipeTool</p>
            <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">Relatórios</h1>
                    <p class="mt-2 text-sm text-slate-500">Escolha um período e baixe os dados em PDF ou CSV.</p>
                </div>
                <p class="text-sm font-medium text-slate-700">
                    Período aplicado:
                    <span class="text-blue-700">{{ \Illuminate\Support\Carbon::parse($de)->format('d/m/Y') }} – {{ \Illuminate\Support\Carbon::parse($ate)->format('d/m/Y') }}</span>
                </p>
            </div>
        </section>

        <section aria-labelledby="periodo-relatorio" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div>
                <h2 id="periodo-relatorio" class="text-base font-semibold text-slate-900">Período dos relatórios</h2>
                <p class="mt-1 text-sm text-slate-500">Os filtros valem para produção e consumo de ingredientes.</p>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($periodosRapidos as $valor => $rotulo)
                    <a
                        href="{{ route('reports.index', ['periodo' => $valor]) }}"
                        @class([
                            'rounded-lg border px-3.5 py-2 text-sm font-semibold no-underline transition',
                            'border-blue-700 bg-blue-700 text-white' => $periodoSelecionado === $valor,
                            'border-slate-300 bg-white text-slate-700 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700' => $periodoSelecionado !== $valor,
                        ])
                        @if ($periodoSelecionado === $valor) aria-current="true" @endif
                    >{{ $rotulo }}</a>
                @endforeach
            </div>

            <form action="{{ route('reports.index') }}" method="GET" class="mt-5 grid gap-4 border-t border-slate-100 pt-5 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                <input type="hidden" name="periodo" value="personalizado">
                <div>
                    <label for="de" class="mb-1.5 block text-sm font-medium text-slate-700">Data inicial</label>
                    <input
                        id="de"
                        name="de"
                        type="date"
                        value="{{ $de }}"
                        max="{{ today()->toDateString() }}"
                        required
                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                </div>
                <div>
                    <label for="ate" class="mb-1.5 block text-sm font-medium text-slate-700">Data final</label>
                    <input
                        id="ate"
                        name="ate"
                        type="date"
                        value="{{ $ate }}"
                        max="{{ today()->toDateString() }}"
                        required
                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                </div>
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Aplicar período
                </button>
            </form>
        </section>

        <section aria-label="Relatórios disponíveis" class="grid gap-5 lg:grid-cols-3">
            <article class="flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-sm font-bold text-indigo-700" aria-hidden="true">PDF</div>
                <h2 class="text-lg font-semibold text-slate-900">Resumo executivo</h2>
                <p class="mt-2 flex-1 text-sm leading-6 text-slate-500">Indicadores de estoque, margem, alertas, custos e produção dos últimos 12 meses. É uma visão geral atual, não limitada ao período selecionado.</p>
                <a href="{{ route('reports.executive.pdf') }}" class="mt-5 inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white no-underline transition hover:bg-blue-800">
                    Baixar PDF executivo
                </a>
            </article>

            <article class="flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-sky-50 text-sm font-bold text-sky-700" aria-hidden="true">01</div>
                <h2 class="text-lg font-semibold text-slate-900">Produção por produto</h2>
                <p class="mt-2 flex-1 text-sm leading-6 text-slate-500">Total de lotes produzidos por produto no período escolhido.</p>
                <div class="mt-5 grid gap-2 sm:grid-cols-2">
                    <a href="{{ route('reports.production.pdf', $filtrosPeriodo) }}" class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-3 py-2.5 text-sm font-semibold text-white no-underline transition hover:bg-blue-800">
                        Baixar PDF
                    </a>
                    <a href="{{ route('reports.production', $filtrosPeriodo) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-semibold text-slate-700 no-underline transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700">
                        Baixar CSV
                    </a>
                </div>
            </article>

            <article class="flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-teal-50 text-sm font-bold text-teal-700" aria-hidden="true">02</div>
                <h2 class="text-lg font-semibold text-slate-900">Consumo de ingredientes</h2>
                <p class="mt-2 flex-1 text-sm leading-6 text-slate-500">Quantidade estimada consumida por ingrediente com base nas produções registradas no período.</p>
                <div class="mt-5 grid gap-2 sm:grid-cols-2">
                    <a href="{{ route('reports.ingredient-consumption.pdf', $filtrosPeriodo) }}" class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-3 py-2.5 text-sm font-semibold text-white no-underline transition hover:bg-blue-800">
                        Baixar PDF
                    </a>
                    <a href="{{ route('reports.ingredient-consumption', $filtrosPeriodo) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-semibold text-slate-700 no-underline transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700">
                        Baixar CSV
                    </a>
                </div>
            </article>
        </section>
    </div>
@endsection
