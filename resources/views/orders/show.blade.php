@extends('layouts.app')

@section('titulo_pagina', 'Pedido #'.$pedido->id)

@section('conteudo')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('orders.index') }}" class="text-sm font-medium text-blue-700 no-underline hover:text-blue-900">← Pedidos e vendas</a>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Pedido #{{ $pedido->id }}</h1>
            <p class="mt-2 text-sm text-slate-500">Registrado em {{ $pedido->data_pedido->format('d/m/Y \à\s H:i') }}</p>
        </div>
        <span class="rounded-full bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-800">{{ $pedido->status_pedido }}</span>
    </div>

    @if (session('success'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('success') }}</div>
    @endif
    @error('status_pedido')
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">{{ $message }}</div>
    @enderror

    <div class="mb-6 grid gap-4 sm:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Responsável pelo pedido</h2>
            <p class="mt-2 font-medium text-slate-900">{{ $pedido->usuario->name }}</p>
            <p class="mt-1 text-sm text-slate-600">{{ $pedido->usuario->email }}</p>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Total do pedido</h2>
            <p class="mt-2 text-2xl font-semibold text-slate-950">R$ {{ number_format((float) $pedido->valor_total, 2, ',', '.') }}</p>
        </section>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4"><h2 class="font-semibold text-slate-900">Produtos</h2></div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50"><tr><th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Produto</th><th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Preço unitário</th><th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Quantidade</th><th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Subtotal</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($pedido->itens as $item)
                        <tr>
                            <td class="px-5 py-4 text-sm font-medium text-slate-900">{{ $item->produto->nome }}</td>
                            <td class="px-5 py-4 text-right text-sm text-slate-600">R$ {{ number_format((float) $item->produto->preco_venda, 2, ',', '.') }}</td>
                            <td class="px-5 py-4 text-right text-sm text-slate-600">{{ $item->qtd }}</td>
                            <td class="px-5 py-4 text-right text-sm font-medium text-slate-900">R$ {{ number_format((float) $item->subtotal, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-slate-900">Histórico de status</h2>
            <ol class="mt-4 grid gap-4">
                @foreach ($pedido->historicoStatus as $historico)
                    <li class="flex gap-3">
                        <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-blue-600" aria-hidden="true"></span>
                        <div>
                            <p class="text-sm font-medium text-slate-900">{{ $historico->status_pedido }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $historico->created_at->format('d/m/Y H:i') }} · {{ $historico->usuario?->name ?? 'Sistema' }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>

        @if ($pedido->status_pedido !== \App\Models\Pedido::STATUS_CANCELADO && $pedido->status_pedido !== 'cancelado')
            <div class="grid content-start gap-4">
                <form action="{{ route('orders.status.update', $pedido) }}" method="POST" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    @csrf
                    @method('PATCH')
                    <label for="status_pedido" class="block text-sm font-medium text-slate-700">Atualizar status</label>
                    <select id="status_pedido" name="status_pedido" required class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        @foreach ($statusDisponiveis as $status)
                            <option value="{{ $status }}" @selected($pedido->status_pedido === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="mt-3 w-full rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">Salvar status</button>
                </form>
                <form action="{{ route('orders.cancel', $pedido) }}" method="POST" class="rounded-2xl border border-red-200 bg-white p-5 shadow-sm" onsubmit="return confirm('Deseja cancelar este pedido e devolver os produtos ao estoque?')">
                    @csrf
                    <p class="text-sm text-slate-600">O cancelamento estorna automaticamente as quantidades dos produtos.</p>
                    <button type="submit" class="mt-3 w-full rounded-lg border border-red-300 px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-50">Cancelar pedido</button>
                </form>
            </div>
        @endif
    </div>
@endsection
