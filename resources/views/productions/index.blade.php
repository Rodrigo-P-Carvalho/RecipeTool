@extends('layouts.app')

@section('titulo_pagina', 'Produção')

@section('conteudo')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-950">Produção</h1>
            <p class="mt-2 text-sm text-slate-500">Histórico de lotes registrados e consumo automático dos ingredientes.</p>
        </div>
        <a href="{{ route('productions.create') }}" class="inline-flex rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white no-underline shadow-sm transition hover:bg-blue-800">Registrar produção</a>
    </div>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50"><tr><th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Data</th><th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Produto</th><th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Lotes</th><th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Registrado por</th><th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Ação</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($producoes as $producao)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $producao->data_producao->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-4 text-sm font-medium text-slate-900">{{ $producao->receita->produto->nome }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $producao->quantidade_lotes }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $producao->usuario->name }}</td>
                            <td class="px-5 py-4 text-right"><a href="{{ route('productions.show', $producao) }}" class="font-medium text-blue-700 no-underline hover:text-blue-900">Detalhes</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">Nenhuma produção registrada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-5">{{ $producoes->links() }}</div>
@endsection
