@extends('layouts.app')

@section('titulo_pagina', 'Registrar produção')

@section('conteudo')
    <div class="mb-6">
        <a href="{{ route('productions.index') }}" class="text-sm font-medium text-blue-700 no-underline hover:text-blue-900">← Produção</a>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Registrar produção</h1>
        <p class="mt-2 text-sm text-slate-500">Os ingredientes serão baixados do estoque e o produto final receberá saldo após a confirmação.</p>
    </div>
    <form action="{{ route('productions.store') }}" method="POST" class="max-w-5xl rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
        @csrf
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-5">
            <div class="xl:col-span-3">
                <label for="receita_id" class="block text-sm font-medium text-slate-700">Receita / produto</label>
                <select id="receita_id" name="receita_id" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 @error('receita_id') border-red-500 @enderror">
                    <option value="">Selecione uma receita</option>
                    @foreach ($receitas as $receita)
                        <option value="{{ $receita->id }}" @selected((string) old('receita_id') === (string) $receita->id)>{{ $receita->produto->nome }} — custo estimado R$ {{ number_format((float) $receita->custo_total, 2, ',', '.') }} por lote</option>
                    @endforeach
                </select>
                @error('receita_id')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div class="xl:col-span-1">
                <label for="quantidade_lotes" class="block text-sm font-medium text-slate-700">Quantidade de lotes</label>
                <input id="quantidade_lotes" name="quantidade_lotes" type="number" min="1" step="1" value="{{ old('quantidade_lotes', 1) }}" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 @error('quantidade_lotes') border-red-500 @enderror">
                @error('quantidade_lotes')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div class="xl:col-span-1">
                <label for="data_producao" class="block text-sm font-medium text-slate-700">Data e hora</label>
                <input id="data_producao" name="data_producao" type="datetime-local" value="{{ old('data_producao', now()->format('Y-m-d\TH:i')) }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 @error('data_producao') border-red-500 @enderror">
                @error('data_producao')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900" role="status">A gravação é transacional: se algum ingrediente não tiver saldo suficiente, nenhum estoque será alterado.</div>
        <div class="mt-8 flex flex-wrap gap-3 border-t border-slate-100 pt-5">
            <button type="submit" class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800">Confirmar produção</button>
            <a href="{{ route('productions.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 no-underline transition hover:bg-slate-50">Cancelar</a>
        </div>
    </form>
@endsection
