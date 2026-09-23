@extends('layouts.app')

@section('titulo_pagina', 'Products')

@section('conteudo')
    <div class="mb-8">
        <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Products</p>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">Product management</h1>
        <p class="mt-3 text-slate-500">Choose an area to continue.</p>
    </div>

    <div class="grid gap-5 md:grid-cols-2">
        <a href="{{ route('products.sales') }}" class="group rounded-2xl border border-slate-200 bg-white p-8 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md">
            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-lg font-bold text-blue-700 transition group-hover:bg-blue-700 group-hover:text-white">S</span>
            <h2 class="mt-6 text-xl font-semibold text-slate-950">Product sales</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">Review the sales log and register a new sale.</p>
            <span class="mt-6 inline-flex items-center text-sm font-semibold text-blue-700">Open sales <span class="ml-2 transition group-hover:translate-x-1">→</span></span>
        </a>

        <a href="{{ route('products.list') }}" class="group rounded-2xl border border-slate-200 bg-white p-8 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md">
            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-lg font-bold text-blue-700 transition group-hover:bg-blue-700 group-hover:text-white">P</span>
            <h2 class="mt-6 text-xl font-semibold text-slate-950">Products list</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">View the full product list and manage products.</p>
            <span class="mt-6 inline-flex items-center text-sm font-semibold text-blue-700">Open product list <span class="ml-2 transition group-hover:translate-x-1">→</span></span>
        </a>
    </div>
@endsection
