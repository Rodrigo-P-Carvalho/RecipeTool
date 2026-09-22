<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('titulo_pagina', 'Recipe Tool') · {{ config('app.name', 'Recipe Tool') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
        <div class="min-h-screen">
            <header class="fixed inset-x-0 top-0 z-30 h-20 border-b border-slate-200 bg-white">
                <div class="flex h-full items-center justify-between px-6 lg:px-8">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-700 text-lg font-bold text-white shadow-sm shadow-blue-700/20">
                            R
                        </span>
                        <span class="text-lg font-semibold tracking-tight text-slate-950">Recipe Tool</span>
                    </a>

                    <div class="flex items-center gap-4">
                        <div class="hidden text-right sm:block">
                            <p class="text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-slate-500">{{ auth()->user()->email }}</p>
                        </div>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-500 transition hover:bg-blue-50 hover:text-blue-700">
                                Log out
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <aside class="fixed bottom-0 left-0 top-20 z-20 hidden w-64 border-r border-slate-200 bg-white lg:block">
                <nav class="space-y-2 p-5" aria-label="Main navigation">
                    <p class="mb-4 px-3 text-xs font-semibold uppercase tracking-widest text-slate-400">Workspace</p>

                    <a href="#" class="flex items-center gap-3 rounded-xl bg-blue-50 px-3 py-3 text-sm font-semibold text-blue-700">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-700 text-white">⌂</span>
                        Overview
                    </a>
                    <a href="#" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-blue-700">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500">P</span>
                        Products
                    </a>
                    <a href="#" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-blue-700">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500">R</span>
                        Recipes
                    </a>
                    <a href="#" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-blue-700">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500">I</span>
                        Ingredients
                    </a>
                    <a href="#" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-blue-700">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500">U</span>
                        Users
                    </a>
                </nav>
            </aside>

            <div class="border-b border-slate-200 bg-white pt-20 lg:hidden">
                <nav class="flex gap-2 overflow-x-auto px-6 py-3" aria-label="Mobile navigation">
                    <a href="#" class="whitespace-nowrap rounded-lg bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700">Overview</a>
                    <a href="#" class="whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Products</a>
                    <a href="#" class="whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Recipes</a>
                    <a href="#" class="whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Ingredients</a>
                    <a href="#" class="whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Users</a>
                </nav>
            </div>

            <main class="min-h-screen pt-20 lg:ml-64">
                <div class="mx-auto max-w-7xl p-6 lg:p-10">
                    @yield('conteudo')
                </div>
            </main>
        </div>
    </body>
</html>
