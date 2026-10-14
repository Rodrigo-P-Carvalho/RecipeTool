@extends('layouts.app')

@section('titulo_pagina', 'Editar receita')

@section('conteudo')
    <div class="mb-6">
        <a href="{{ route('recipes.show', $receita) }}" class="text-sm font-medium text-blue-700 no-underline hover:text-blue-900">← {{ $receita->produto->nome }}</a>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Editar receita</h1>
    </div>
    <form action="{{ route('recipes.update', $receita) }}" method="POST" class="max-w-5xl rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
        @csrf
        @method('PUT')
        @include('recipes._form')
        <div class="mt-8 flex flex-wrap gap-3 border-t border-slate-100 pt-5">
            <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">Salvar alterações</button>
            <a href="{{ route('recipes.show', $receita) }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 no-underline transition hover:bg-slate-50">Cancelar</a>
        </div>
    </form>
@endsection
