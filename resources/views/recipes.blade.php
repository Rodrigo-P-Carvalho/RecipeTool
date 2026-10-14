@extends('layouts.app')

@section('titulo_pagina', 'Receitas')

@section('conteudo')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-950">Receitas</h1>
            <p class="mt-2 text-sm text-slate-500">Consulte os ingredientes e o custo calculado de cada receita.</p>
        </div>
        <a href="{{ route('recipes.create') }}" class="inline-flex items-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white no-underline shadow-sm transition hover:bg-blue-800">Cadastrar receita</a>
    </div>

    <form action="{{ route('recipes') }}" method="GET" role="search" class="mb-5 flex flex-col gap-3 sm:flex-row">
        <div class="flex-1">
            <label for="recipe-search" class="sr-only">Buscar receita por produto ou descrição</label>
            <input id="recipe-search" type="search" name="search" value="{{ $search }}" placeholder="Buscar por produto ou descrição..." class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm outline-none placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">Buscar</button>
            @if ($search !== '')
                <a href="{{ route('recipes') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 no-underline transition hover:bg-slate-50">Limpar</a>
            @endif
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Produto</th>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Descrição</th>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Custo total</th>
                        <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($receitas as $receita)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-900">{{ $receita->produto->nome }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $receita->descricao }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-900">R$ {{ number_format((float) $receita->custo_total, 2, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right text-sm">
                                <a href="{{ route('recipes.show', $receita) }}" class="font-medium text-blue-700 no-underline hover:text-blue-900">Ver</a>
                                <a href="{{ route('recipes.edit', $receita) }}" class="ml-3 font-medium text-slate-600 no-underline hover:text-slate-900">Editar</a>
                                <form action="{{ route('recipes.destroy', $receita) }}" method="POST" class="ml-3 inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-medium text-red-700 hover:text-red-900">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-12 text-center text-sm text-slate-500">{{ $search !== '' ? "Nenhuma receita encontrada para \"{$search}\"." : 'Nenhuma receita cadastrada.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $receitas->links() }}</div>
@endsection
