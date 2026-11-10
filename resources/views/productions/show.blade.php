@extends('layouts.app')

@section('titulo_pagina', 'Produção #'.$producao->id)

@section('conteudo')
    <div class="mb-6">
        <a href="{{ route('productions.index') }}" class="text-sm font-medium text-blue-700 no-underline hover:text-blue-900">← Produção</a>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Produção #{{ $producao->id }}</h1>
        <p class="mt-2 text-sm text-slate-500">{{ $producao->receita->produto->nome }}</p>
    </div>
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Lotes produzidos</p><p class="mt-2 text-2xl font-semibold text-slate-950">{{ $producao->quantidade_lotes }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Data</p><p class="mt-2 text-lg font-semibold text-slate-950">{{ $producao->data_producao->format('d/m/Y H:i') }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Registrado por</p><p class="mt-2 text-lg font-semibold text-slate-950">{{ $producao->usuario->name }}</p></div>
    </div>
    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4"><h2 class="font-semibold text-slate-900">Consumo de ingredientes</h2></div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50"><tr><th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Ingrediente</th><th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Quantidade por lote</th><th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Total consumido</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($producao->receita->itens as $item)
                        @php($unidade = $item->ingrediente->unidade_medida)
                        @php($quantidadeTotal = $unidade->converterDaBase((float) $item->quantidade_necessaria * $producao->quantidade_lotes))
                        <tr>
                            <td class="px-5 py-4 text-sm font-medium text-slate-900">{{ $item->ingrediente->nome }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $item->quantidade_necessaria_formatada }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ number_format($quantidadeTotal, 3, ',', '.') }} {{ $unidade->value }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
