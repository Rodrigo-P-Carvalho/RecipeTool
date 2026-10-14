@extends('layouts.app')

@section('titulo_pagina', 'Novo ingrediente')

@section('conteudo')
    <div class="mb-6">
        <a href="{{ route('ingredients') }}" class="text-sm font-medium text-blue-700 no-underline hover:text-blue-900">← Ingredientes</a>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Cadastrar ingrediente</h1>
        <p class="mt-2 text-sm text-slate-500">Informe o saldo inicial e os dados da embalagem para calcular o custo unitário.</p>
    </div>

    <form action="{{ route('ingredients.store') }}" method="POST" class="max-w-4xl rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
        @csrf
        @include('ingredients._form')
        <div class="mt-8 flex flex-wrap gap-3 border-t border-slate-100 pt-5">
            <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Salvar ingrediente</button>
            <a href="{{ route('ingredients') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 no-underline transition hover:bg-slate-50">Cancelar</a>
        </div>
    </form>
@endsection
