@extends('layouts.app')

@section('titulo_pagina', 'Ingredientes')

@section('conteudo')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-950">Estoque de ingredientes</h1>
            <p class="mt-2 text-sm text-slate-500">Acompanhe insumos, custos e níveis mínimos de estoque.</p>
        </div>
        <a href="{{ route('ingredients.create') }}" class="inline-flex items-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white no-underline shadow-sm transition hover:bg-blue-800">Cadastrar ingrediente</a>
    </div>

    <form action="{{ route('ingredients') }}" method="GET" role="search" class="mb-5 flex flex-col gap-3 sm:flex-row">
        <div class="flex-1">
            <label for="ingredient-search" class="sr-only">Buscar ingrediente por nome</label>
            <input id="ingredient-search" type="search" name="search" value="{{ $search }}" placeholder="Buscar ingrediente por nome..." class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm outline-none placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">Buscar</button>
            @if ($search !== '')
                <a href="{{ route('ingredients') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 no-underline transition hover:bg-slate-50">Limpar</a>
            @endif
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Ingrediente</th>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Saldo</th>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Mínimo</th>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Custo unitário</th>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($ingredientes as $ingrediente)
                        @php($estoqueBaixo = (float) $ingrediente->quantidade_estoque <= (float) $ingrediente->estoque_minimo)
                        <tr @class(['bg-red-50' => $estoqueBaixo, 'hover:bg-slate-50' => ! $estoqueBaixo])>
                            <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-900">{{ $ingrediente->nome }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ number_format((float) $ingrediente->quantidade_estoque, 3, ',', '.') }} {{ $ingrediente->unidade_medida }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ number_format((float) $ingrediente->estoque_minimo, 3, ',', '.') }} {{ $ingrediente->unidade_medida }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">R$ {{ number_format((float) $ingrediente->custo_unitario, 4, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-800' => $ingrediente->status_ingrediente === 'ativo',
                                    'bg-slate-100 text-slate-600' => $ingrediente->status_ingrediente !== 'ativo',
                                ])>{{ ucfirst($ingrediente->status_ingrediente) }}</span>
                                @if ($estoqueBaixo)
                                    <span class="ml-1 inline-flex rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-800">Estoque baixo</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right text-sm">
                                <a href="{{ route('ingredients.show', $ingrediente) }}" class="font-medium text-blue-700 no-underline hover:text-blue-900">Ver</a>
                                <a href="{{ route('ingredients.edit', $ingrediente) }}" class="ml-3 font-medium text-slate-600 no-underline hover:text-slate-900">Editar</a>
                                <form action="{{ route('ingredients.destroy', $ingrediente) }}" method="POST" class="ml-3 inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-medium text-red-700 hover:text-red-900">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500">{{ $search !== '' ? "Nenhum ingrediente encontrado para \"{$search}\"." : 'Nenhum ingrediente cadastrado.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $ingredientes->links() }}</div>
@endsection
