@extends('layouts.app')

@section('titulo_pagina', 'Nova receita')

@section('conteudo')
    <div class="mb-6">
        <a href="{{ route('recipes') }}" class="text-sm font-medium text-blue-700 no-underline hover:text-blue-900">← Receitas</a>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Cadastrar receita</h1>
        <p class="mt-2 text-sm text-slate-500">Associe um produto aos ingredientes e quantidades necessários para produzi-lo.</p>
    </div>
    <form action="{{ route('recipes.store') }}" method="POST" class="max-w-5xl rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
        @csrf
        @include('recipes._form')
        <div class="mt-8 flex flex-wrap gap-3 border-t border-slate-100 pt-5">
            <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">Salvar receita</button>
            <a href="{{ route('recipes') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 no-underline transition hover:bg-slate-50">Cancelar</a>
        </div>
    </form>
@endsection
