@extends('layouts.app')

@section('titulo_pagina', 'Pedidos e vendas')

@section('conteudo')
    <div class="mx-auto flex min-h-[60vh] max-w-3xl items-center justify-center py-10">
        <section class="w-full rounded-3xl border border-slate-200 bg-white p-8 text-center shadow-sm sm:p-12" aria-labelledby="orders-title">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-3xl" aria-hidden="true">🧁</div>
            <p class="mt-6 text-sm font-semibold uppercase tracking-widest text-blue-700">Novidade a caminho</p>
            <h1 id="orders-title" class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">Pedidos e vendas</h1>
            <p class="mt-4 text-base leading-7 text-slate-600">
                Estamos preparando esta área para ajudar você a registrar pedidos, acompanhar vendas e consultar o histórico.
            </p>
            <span class="mt-6 inline-flex rounded-full bg-amber-100 px-4 py-2 text-sm font-semibold text-amber-800">Em breve</span>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white no-underline shadow-sm transition hover:bg-blue-800">Voltar ao painel</a>
                <a href="{{ route('stock.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 no-underline transition hover:bg-slate-50">Consultar estoque</a>
            </div>
        </section>
    </div>
@endsection
