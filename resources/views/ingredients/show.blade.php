@extends('layouts.app')

@section('titulo_pagina', $ingrediente->nome)

@section('conteudo')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('ingredients') }}" class="text-sm font-medium text-blue-700 no-underline hover:text-blue-900">← Estoque de ingredientes</a>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ $ingrediente->nome }}</h1>
        </div>
        <a href="{{ route('ingredients.edit', $ingrediente) }}" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white no-underline transition hover:bg-blue-800">Editar ingrediente</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Saldo atual</p><p class="mt-2 text-2xl font-semibold text-slate-950">{{ $ingrediente->quantidade_estoque_formatada }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Estoque mínimo</p><p class="mt-2 text-2xl font-semibold text-slate-950">{{ $ingrediente->estoque_minimo_formatado }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Custo por unidade de exibição</p><p class="mt-2 text-2xl font-semibold text-slate-950">{{ $ingrediente->custo_formatado }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Preço da embalagem</p><p class="mt-2 text-2xl font-semibold text-slate-950">R$ {{ number_format((float) $ingrediente->preco_embalagem, 2, ',', '.') }}</p><p class="mt-1 text-sm text-slate-500">{{ $ingrediente->tamanho_embalagem_formatado }}</p></div>
    </div>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Informações do ingrediente</h2>
        <dl class="mt-4 grid gap-4 sm:grid-cols-2">
            <div><dt class="text-sm text-slate-500">Status</dt><dd class="mt-1 font-medium text-slate-900">{{ ucfirst($ingrediente->status_ingrediente) }}</dd></div>
            <div><dt class="text-sm text-slate-500">Cadastrado em</dt><dd class="mt-1 font-medium text-slate-900">{{ $ingrediente->created_at?->format('d/m/Y H:i') }}</dd></div>
        </dl>
    </div>
@endsection
