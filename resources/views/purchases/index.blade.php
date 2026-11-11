@extends('layouts.app')

@section('titulo_pagina', 'Compras e lotes')

@section('conteudo')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-950">Compras e lotes</h1>
            <p class="mt-2 text-sm text-slate-500">Histórico de entradas de ingredientes e datas de validade.</p>
        </div>
        <a href="{{ route('purchases.create') }}" class="inline-flex rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white no-underline shadow-sm hover:bg-blue-800">Registrar compra</a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Compra</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Data</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Responsável</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Lotes</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($compras as $compra)
                        <tr class="align-top hover:bg-slate-50">
                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">#{{ $compra->id }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $compra->data_compra->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $compra->usuario->name }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">
                                <ul class="space-y-2">
                                    @foreach ($compra->lotes as $lote)
                                        <li>
                                            {{ $lote->ingrediente->nome }} — lote {{ $lote->codigo_lote }},
                                            validade {{ $lote->data_validade->format('d/m/Y') }},
                                            {{ $lote->quantidade_inicial_formatada }}
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                            <td class="px-5 py-4 text-right text-sm font-semibold text-slate-900">R$ {{ number_format((float) $compra->valor_total, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">Nenhuma compra registrada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $compras->links() }}</div>
@endsection
