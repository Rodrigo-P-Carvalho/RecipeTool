@extends('layouts.app')

@section('titulo_pagina', 'Cadastrar usuário')

@section('conteudo')
    <div class="mb-6">
        <h1 class="text-3xl font-semibold tracking-tight text-slate-950">Cadastrar usuário</h1>
        <p class="mt-2 text-sm text-slate-500">Crie uma conta e escolha o nível de acesso.</p>
    </div>

    <div class="max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <form action="{{ route('users.store') }}" method="POST">
            @csrf
            @include('usuarios._form', ['botao' => 'Criar usuário'])
        </form>
    </div>
@endsection
