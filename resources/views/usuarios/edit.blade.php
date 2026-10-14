@extends('layouts.app')

@section('titulo_pagina', 'Editar usuário')

@section('conteudo')
    <div class="mb-6">
        <h1 class="text-3xl font-semibold tracking-tight text-slate-950">Editar usuário</h1>
        <p class="mt-2 text-sm text-slate-500">Atualize os dados e o nível de acesso de {{ $usuario->name }}.</p>
    </div>

    <div class="max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <form action="{{ route('users.update', $usuario) }}" method="POST">
            @csrf
            @method('PUT')
            @include('usuarios._form', ['botao' => 'Salvar alterações'])
        </form>
    </div>
@endsection
