@extends('layouts.app')

@section('titulo_pagina', 'Painel inicial')

@section('conteudo')
    <section class="overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-sm">
        <div class="bg-gradient-to-r from-blue-700 to-indigo-700 px-6 py-8 text-white sm:px-8 sm:py-10">
            <p class="text-sm font-semibold uppercase tracking-widest text-blue-100">RecipeTool · Gestão de confeitaria</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">Olá, {{ auth()->user()->name }}.</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-blue-100 sm:text-base">
                Você está conectado como {{ auth()->user()->name }} ({{ auth()->user()->email }}).
                Acesse as áreas de estoque, receitas e produção pelo menu lateral.
            </p>
        </div>
        <div class="grid gap-4 p-5 sm:grid-cols-2 sm:p-7 lg:grid-cols-3">
            <a href="{{ route('ingredients') }}" class="group rounded-xl border border-slate-200 p-5 no-underline transition hover:border-blue-300 hover:bg-blue-50/50">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 font-semibold text-blue-700">I</span>
                <h2 class="mt-4 text-base font-semibold text-slate-900">Estoque de ingredientes</h2>
                <p class="mt-1 text-sm text-slate-500">Consulte saldos, custos unitários e alertas de reposição.</p>
            </a>
            <a href="{{ route('products') }}" class="group rounded-xl border border-slate-200 p-5 no-underline transition hover:border-blue-300 hover:bg-blue-50/50">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-100 font-semibold text-indigo-700">P</span>
                <h2 class="mt-4 text-base font-semibold text-slate-900">Estoque de produtos</h2>
                <p class="mt-1 text-sm text-slate-500">Veja os produtos prontos e seus preços de venda.</p>
            </a>
            <a href="{{ route('recipes') }}" class="group rounded-xl border border-slate-200 p-5 no-underline transition hover:border-blue-300 hover:bg-blue-50/50">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-violet-100 font-semibold text-violet-700">R</span>
                <h2 class="mt-4 text-base font-semibold text-slate-900">Receitas</h2>
                <p class="mt-1 text-sm text-slate-500">Gerencie ingredientes e custo de cada produto.</p>
            </a>
            <a href="{{ route('productions.create') }}" class="group rounded-xl border border-slate-200 p-5 no-underline transition hover:border-blue-300 hover:bg-blue-50/50">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 font-semibold text-emerald-700">F</span>
                <h2 class="mt-4 text-base font-semibold text-slate-900">Registrar produção</h2>
                <p class="mt-1 text-sm text-slate-500">Produza lotes e atualize os estoques em uma operação.</p>
            </a>
            <a href="{{ route('stock-adjustments.index') }}" class="group rounded-xl border border-slate-200 p-5 no-underline transition hover:border-blue-300 hover:bg-blue-50/50">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 font-semibold text-amber-700">±</span>
                <h2 class="mt-4 text-base font-semibold text-slate-900">Movimentações</h2>
                <p class="mt-1 text-sm text-slate-500">Consulte o histórico ou registre um ajuste manual.</p>
            </a>
            <a href="{{ route('users') }}" class="group rounded-xl border border-slate-200 p-5 no-underline transition hover:border-blue-300 hover:bg-blue-50/50">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 font-semibold text-slate-700">U</span>
                <h2 class="mt-4 text-base font-semibold text-slate-900">Usuários</h2>
                <p class="mt-1 text-sm text-slate-500">Acesse a área de usuários do sistema.</p>
            </a>
        </div>
    </section>
@endsection
