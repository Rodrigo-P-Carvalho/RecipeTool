@extends('layouts.app')

@section('titulo_pagina', 'Movimentação de estoque')

@section('conteudo')
    <div class="mb-6">
        <a href="{{ route('stock-adjustments.index') }}" class="text-sm font-medium text-blue-700 no-underline hover:text-blue-900">← Movimentações de estoque</a>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Detalhes da movimentação</h1>
    </div>
    <div class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <dl class="grid gap-x-6 gap-y-5 sm:grid-cols-2">
            <div><dt class="text-sm text-slate-500">Item</dt><dd class="mt-1 font-medium text-slate-900">{{ $ajuste->ajustavel?->nome ?? 'Item removido' }}</dd></div>
            <div><dt class="text-sm text-slate-500">Tipo</dt><dd class="mt-1 font-medium text-slate-900">{{ str_replace('_', ' ', ucfirst($ajuste->tipo_ajuste)) }}</dd></div>
            <div><dt class="text-sm text-slate-500">Quantidade</dt><dd class="mt-1 font-medium text-slate-900">{{ $ajuste->quantidade_formatada }}</dd></div>
            <div><dt class="text-sm text-slate-500">Data e hora</dt><dd class="mt-1 font-medium text-slate-900">{{ $ajuste->data_ajuste->format('d/m/Y H:i') }}</dd></div>
            <div><dt class="text-sm text-slate-500">Responsável</dt><dd class="mt-1 font-medium text-slate-900">{{ $ajuste->usuario->name }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-sm text-slate-500">Justificativa</dt><dd class="mt-1 font-medium text-slate-900">{{ $ajuste->justificativa }}</dd></div>
        </dl>
    </div>
@endsection
