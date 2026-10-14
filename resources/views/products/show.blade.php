@extends('layouts.app')

@section('titulo_pagina', $produto->nome)

@section('conteudo')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('products') }}" class="text-sm font-medium text-blue-700 no-underline hover:text-blue-900">← Estoque de produtos</a>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ $produto->nome }}</h1>
        </div>
        <a href="{{ route('products.edit', $produto) }}" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white no-underline transition hover:bg-blue-800">Editar produto</a>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><p class="text-sm text-slate-500">Saldo em estoque</p><p class="mt-2 text-3xl font-semibold text-slate-950">{{ number_format((float) $produto->quantidade_estoque, 3, ',', '.') }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><p class="text-sm text-slate-500">Preço de venda</p><p class="mt-2 text-3xl font-semibold text-slate-950">R$ {{ number_format((float) $produto->preco_venda, 2, ',', '.') }}</p></div>
    </div>
    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Descrição</h2>
        <p class="mt-3 text-sm leading-6 text-slate-600">{{ $produto->descricao }}</p>
    </div>
    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Receita</h2>
        <div class="mt-3 text-sm">
            @if ($produto->receita)
                <a href="{{ route('recipes.show', $produto->receita) }}" class="font-medium text-blue-700 no-underline hover:text-blue-900">{{ $produto->receita->descricao }}</a>
            @else
                <span class="text-slate-500">Este produto ainda não tem uma receita cadastrada.</span>
                <a href="{{ route('recipes.create') }}" class="ml-2 font-medium text-blue-700 no-underline hover:text-blue-900">Criar receita</a>
            @endif
        </div>
    </div>
@endsection
