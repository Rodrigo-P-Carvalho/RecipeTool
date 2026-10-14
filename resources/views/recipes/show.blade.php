@extends('layouts.app')

@section('titulo_pagina', 'Receita de '.$receita->produto->nome)

@section('conteudo')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('recipes') }}" class="text-sm font-medium text-blue-700 no-underline hover:text-blue-900">← Receitas</a>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ $receita->produto->nome }}</h1>
            <p class="mt-2 text-sm text-slate-500">{{ $receita->descricao }}</p>
        </div>
        <a href="{{ route('recipes.edit', $receita) }}" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white no-underline transition hover:bg-blue-800">Editar receita</a>
    </div>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4"><h2 class="font-semibold text-slate-900">Ingredientes por lote</h2></div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50"><tr><th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Ingrediente</th><th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Quantidade</th><th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Custo unitário</th><th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Subtotal</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($receita->itens as $item)
                        <tr>
                            <td class="px-5 py-4 text-sm font-medium text-slate-900">{{ $item->ingrediente->nome }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ number_format((float) $item->quantidade_necessaria, 3, ',', '.') }} {{ $item->ingrediente->unidade_medida }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">R$ {{ number_format((float) $item->ingrediente->custo_unitario, 4, ',', '.') }}</td>
                            <td class="px-5 py-4 text-right text-sm text-slate-600">R$ {{ number_format((float) $item->quantidade_necessaria * (float) $item->ingrediente->custo_unitario, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50"><tr><th colspan="3" class="px-5 py-4 text-right text-sm font-semibold text-slate-700">Custo total estimado por lote</th><th class="px-5 py-4 text-right text-sm font-semibold text-slate-900">R$ {{ number_format((float) $receita->custo_total, 2, ',', '.') }}</th></tr></tfoot>
            </table>
        </div>
    </div>
    <a href="{{ route('productions.create') }}" class="mt-5 inline-flex rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white no-underline transition hover:bg-emerald-800">Registrar produção</a>
@endsection
