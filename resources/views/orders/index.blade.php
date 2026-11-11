@extends('layouts.app')

@section('titulo_pagina', 'Pedidos e vendas')

@section('conteudo')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-950">Pedidos e vendas</h1>
            <p class="mt-2 text-sm text-slate-500">Acompanhe pedidos, valores e situação das vendas.</p>
        </div>
        <a href="{{ route('orders.create') }}" class="inline-flex rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white no-underline shadow-sm transition hover:bg-blue-800">Novo pedido</a>
    </div>

    <form method="GET" action="{{ route('orders.index') }}" class="mb-5 flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="min-w-52">
            <label for="status_pedido" class="block text-sm font-medium text-slate-700">Filtrar por status</label>
            <select id="status_pedido" name="status_pedido" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                <option value="">Todos os status</option>
                @foreach ($statusDisponiveis as $status)
                    <option value="{{ $status }}" @selected($statusPedido === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-900">Filtrar</button>
        @if ($statusPedido !== '')
            <a href="{{ route('orders.index') }}" class="px-2 py-2.5 text-sm font-medium text-blue-700 no-underline hover:text-blue-900">Limpar filtro</a>
        @endif
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Pedido</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Data</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Responsável</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Itens</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Total</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Ação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($pedidos as $pedido)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-900">#{{ $pedido->id }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $pedido->data_pedido->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $pedido->usuario->name }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $pedido->itens_count }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $pedido->status_pedido }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-medium text-slate-900">R$ {{ number_format((float) $pedido->valor_total, 2, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right text-sm"><a href="{{ route('orders.show', $pedido) }}" class="font-medium text-blue-700 no-underline hover:text-blue-900">Detalhes</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-12 text-center text-sm text-slate-500">Nenhum pedido encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-5">{{ $pedidos->links() }}</div>
@endsection
