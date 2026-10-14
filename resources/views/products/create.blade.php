@extends('layouts.app')

@section('titulo_pagina', 'Novo produto')

@section('conteudo')
    <div class="mb-6">
        <a href="{{ route('products') }}" class="text-sm font-medium text-blue-700 no-underline hover:text-blue-900">← Produtos</a>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Cadastrar produto</h1>
        <p class="mt-2 text-sm text-slate-500">Cadastre um produto final e informe o saldo inicial disponível.</p>
    </div>
    <form action="{{ route('products.store') }}" method="POST" class="max-w-4xl rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
        @csrf
        @include('products._form')
        <div class="mt-8 flex flex-wrap gap-3 border-t border-slate-100 pt-5">
            <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">Salvar produto</button>
            <a href="{{ route('products') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 no-underline transition hover:bg-slate-50">Cancelar</a>
        </div>
    </form>
@endsection
