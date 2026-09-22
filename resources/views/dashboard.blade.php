@extends('layouts.app')

@section('titulo_pagina', 'Overview')

@section('conteudo')
    <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Recipe Tool</p>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">Welcome to your workspace.</h1>
        <p class="mt-3 text-slate-500">
            You are logged in as {{ auth()->user()->name }} ({{ auth()->user()->email }}).
        </p>
    </div>
@endsection
