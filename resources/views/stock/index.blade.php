@extends('layouts.app')

@section('titulo_pagina', 'Estoque')

@section('conteudo')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-950">Visão geral do estoque</h1>
            <p class="mt-2 text-sm text-slate-500">Saldos atuais de ingredientes e produtos prontos.</p>
        </div>
        <a href="{{ route('stock-adjustments.create') }}" class="inline-flex rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white no-underline shadow-sm transition hover:bg-blue-800">Ajustar estoque</a>
    </div>

    @if ($ingredientesBaixoEstoque->isNotEmpty())
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-5 text-red-900" role="alert">
            <h2 class="text-lg font-semibold">Atenção: estoque mínimo atingido</h2>
            <p class="mt-1 text-sm">Os seguintes ingredientes precisam de reposição:</p>
            <ul class="mt-3 list-inside list-disc space-y-1 text-sm">
                @foreach ($ingredientesBaixoEstoque as $ingrediente)
                    <li>{{ $ingrediente->nome }} — saldo {{ number_format((float) $ingrediente->quantidade_estoque, 3, ',', '.') }} {{ $ingrediente->unidade_medida }} (mínimo {{ number_format((float) $ingrediente->estoque_minimo, 3, ',', '.') }} {{ $ingrediente->unidade_medida }})</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section aria-labelledby="ingredientes-titulo">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <h2 id="ingredientes-titulo" class="text-xl font-semibold text-slate-900">Estoque de ingredientes</h2>
            <a href="{{ route('ingredients') }}" class="text-sm font-medium text-blue-700 no-underline hover:text-blue-900">Gerenciar ingredientes →</a>
        </div>
        <form action="{{ route('stock.index') }}" method="GET" role="search" class="mb-4 flex flex-col gap-3 sm:flex-row">
            <div class="flex-1">
                <label for="stock-ingredient-search" class="sr-only">Buscar ingrediente por nome</label>
                <input id="stock-ingredient-search" type="search" name="busca_ingrediente" value="{{ $buscaIngrediente }}" placeholder="Buscar ingrediente por nome..." class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm outline-none placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">Buscar</button>
                @if ($buscaIngrediente !== '')
                    <a href="{{ route('stock.index', ['busca_produto' => $buscaProduto]) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 no-underline transition hover:bg-slate-50">Limpar</a>
                @endif
            </div>
        </form>
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50"><tr><th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Ingrediente</th><th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Saldo</th><th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Estoque mínimo</th><th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Situação</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($ingredientes as $ingrediente)
                            @php($estoqueBaixo = (float) $ingrediente->quantidade_estoque <= (float) $ingrediente->estoque_minimo)
                            <tr @class(['bg-red-50' => $estoqueBaixo, 'hover:bg-slate-50' => ! $estoqueBaixo])>
                                <td class="px-5 py-4 text-sm font-semibold text-slate-900">{{ $ingrediente->nome }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ number_format((float) $ingrediente->quantidade_estoque, 3, ',', '.') }} {{ $ingrediente->unidade_medida }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ number_format((float) $ingrediente->estoque_minimo, 3, ',', '.') }} {{ $ingrediente->unidade_medida }}</td>
                                <td class="px-5 py-4">
                                    @if ($estoqueBaixo)
                                        <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-800">Repor</span>
                                    @else
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">Disponível</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-10 text-center text-sm text-slate-500">{{ $buscaIngrediente !== '' ? "Nenhum ingrediente encontrado para \"{$buscaIngrediente}\"." : 'Nenhum ingrediente cadastrado.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-4">{{ $ingredientes->links() }}</div>
    </section>

    <section aria-labelledby="produtos-titulo" class="mt-10">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <h2 id="produtos-titulo" class="text-xl font-semibold text-slate-900">Estoque de produtos prontos</h2>
            <a href="{{ route('products') }}" class="text-sm font-medium text-blue-700 no-underline hover:text-blue-900">Gerenciar produtos →</a>
        </div>
        <form action="{{ route('stock.index') }}" method="GET" role="search" class="mb-4 flex flex-col gap-3 sm:flex-row">
            <div class="flex-1">
                <label for="stock-product-search" class="sr-only">Buscar produto por nome</label>
                <input id="stock-product-search" type="search" name="busca_produto" value="{{ $buscaProduto }}" placeholder="Buscar produto por nome..." class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm outline-none placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">Buscar</button>
                @if ($buscaProduto !== '')
                    <a href="{{ route('stock.index', ['busca_ingrediente' => $buscaIngrediente]) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 no-underline transition hover:bg-slate-50">Limpar</a>
                @endif
            </div>
        </form>
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50"><tr><th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Produto</th><th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Saldo disponível</th><th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Preço de venda</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($produtos as $produto)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-4 text-sm font-semibold text-slate-900">{{ $produto->nome }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ number_format((float) $produto->quantidade_estoque, 3, ',', '.') }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">R$ {{ number_format((float) $produto->preco_venda, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-5 py-10 text-center text-sm text-slate-500">{{ $buscaProduto !== '' ? "Nenhum produto encontrado para \"{$buscaProduto}\"." : 'Nenhum produto cadastrado.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-4">{{ $produtos->links() }}</div>
    </section>
@endsection
