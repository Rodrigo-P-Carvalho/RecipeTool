@extends('layouts.app')

@section('titulo_pagina', 'Ajustes de estoque')

@section('conteudo')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-950">Movimentações de estoque</h1>
            <p class="mt-2 text-sm text-slate-500">Histórico de entradas, saídas, produções e ajustes manuais.</p>
        </div>
        <a href="{{ route('stock-adjustments.create') }}" class="inline-flex rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white no-underline shadow-sm transition hover:bg-blue-800">Novo ajuste manual</a>
    </div>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50"><tr><th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Data</th><th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Item</th><th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tipo</th><th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Quantidade</th><th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Responsável</th><th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Ação</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($ajustes as $ajuste)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $ajuste->data_ajuste->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-4 text-sm font-medium text-slate-900">{{ $ajuste->ajustavel?->nome ?? 'Item removido' }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ str_replace('_', ' ', ucfirst($ajuste->tipo_ajuste)) }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $ajuste->quantidade_formatada }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $ajuste->usuario->name }}</td>
                            <td class="px-5 py-4 text-right"><a href="{{ route('stock-adjustments.show', $ajuste) }}" class="font-medium text-blue-700 no-underline hover:text-blue-900">Detalhes</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500">Nenhuma movimentação registrada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-5">{{ $ajustes->links() }}</div>
@endsection
