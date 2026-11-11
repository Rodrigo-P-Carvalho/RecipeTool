<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('titulo_pagina', 'RecipeTool') · {{ config('app.name', 'RecipeTool') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('estilos')
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
        <header class="fixed inset-x-0 top-0 z-50 h-20 border-b border-slate-200 bg-white">
            <div class="flex h-full items-center justify-between px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <button
                        id="menu-lateral-botao"
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500 lg:hidden"
                        aria-label="Abrir menu"
                        aria-controls="menu-lateral"
                        aria-expanded="false"
                    >
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 no-underline">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-700 text-lg font-bold text-white shadow-sm shadow-blue-700/20">R</span>
                        <span class="text-lg font-semibold tracking-tight text-slate-950">RecipeTool</span>
                    </a>
                </div>

                @auth
                    <div class="flex items-center gap-2 sm:gap-5">
                        <div class="max-w-28 text-right sm:max-w-none">
                            <p class="truncate text-xs font-semibold text-slate-900 sm:text-sm">{{ auth()->user()->name }}</p>
                            <p class="hidden text-xs text-slate-500 sm:block">{{ auth()->user()->email }}</p>
                        </div>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                Sair
                            </button>
                        </form>
                    </div>
                @endauth
            </div>
        </header>

        <div id="menu-lateral-fundo" class="fixed inset-0 top-20 z-30 hidden bg-slate-950/40 lg:hidden"></div>

        <aside
            id="menu-lateral"
            class="fixed bottom-0 left-0 top-20 z-40 w-72 -translate-x-full overflow-y-auto border-r border-slate-200 bg-white transition-transform duration-200 ease-in-out lg:translate-x-0"
            aria-label="Navegação principal"
        >
            <nav class="space-y-7 p-5">
                <div class="space-y-1">
                    <p class="mb-3 px-3 text-xs font-semibold uppercase tracking-widest text-slate-400">Visão geral</p>
                    <a href="{{ route('dashboard') }}" @class([
                        'flex items-center gap-3 rounded-xl px-3 py-3 text-sm no-underline transition',
                        'bg-blue-50 font-semibold text-blue-700' => request()->routeIs('dashboard'),
                        'font-medium text-slate-600 hover:bg-slate-50 hover:text-blue-700' => ! request()->routeIs('dashboard'),
                    ])>
                        <span @class([
                            'flex h-8 w-8 items-center justify-center rounded-lg',
                            'bg-blue-700 text-white' => request()->routeIs('dashboard'),
                            'bg-slate-100 text-slate-500' => ! request()->routeIs('dashboard'),
                        ]) aria-hidden="true">⌂</span>
                        Painel inicial
                    </a>
                    <a href="{{ route('stock.index') }}" @class([
                        'flex items-center gap-3 rounded-xl px-3 py-3 text-sm no-underline transition',
                        'bg-blue-50 font-semibold text-blue-700' => request()->routeIs('stock.index'),
                        'font-medium text-slate-600 hover:bg-slate-50 hover:text-blue-700' => ! request()->routeIs('stock.index'),
                    ])>
                        <span @class([
                            'flex h-8 w-8 items-center justify-center rounded-lg',
                            'bg-blue-700 text-white' => request()->routeIs('stock.index'),
                            'bg-slate-100 text-slate-500' => ! request()->routeIs('stock.index'),
                        ]) aria-hidden="true">E</span>
                        Visão geral do estoque
                    </a>
                </div>

                <div class="space-y-1">
                    <p class="mb-3 px-3 text-xs font-semibold uppercase tracking-widest text-slate-400">Estoque e catálogo</p>
                    <a href="{{ route('ingredients') }}" @class([
                        'flex items-center gap-3 rounded-xl px-3 py-3 text-sm no-underline transition',
                        'bg-blue-50 font-semibold text-blue-700' => request()->routeIs('ingredients*'),
                        'font-medium text-slate-600 hover:bg-slate-50 hover:text-blue-700' => ! request()->routeIs('ingredients*'),
                    ])>
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500" aria-hidden="true">I</span>
                        Estoque de ingredientes
                    </a>
                    <a href="{{ route('purchases.index') }}" @class([
                        'flex items-center gap-3 rounded-xl px-3 py-3 text-sm no-underline transition',
                        'bg-blue-50 font-semibold text-blue-700' => request()->routeIs('purchases*'),
                        'font-medium text-slate-600 hover:bg-slate-50 hover:text-blue-700' => ! request()->routeIs('purchases*'),
                    ])>
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500" aria-hidden="true">C</span>
                        Compras e lotes
                    </a>
                    <a href="{{ route('products') }}" @class([
                        'flex items-center gap-3 rounded-xl px-3 py-3 text-sm no-underline transition',
                        'bg-blue-50 font-semibold text-blue-700' => request()->routeIs('products*'),
                        'font-medium text-slate-600 hover:bg-slate-50 hover:text-blue-700' => ! request()->routeIs('products*'),
                    ])>
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500" aria-hidden="true">P</span>
                        Estoque de produtos
                    </a>
                    <a href="{{ route('recipes') }}" @class([
                        'flex items-center gap-3 rounded-xl px-3 py-3 text-sm no-underline transition',
                        'bg-blue-50 font-semibold text-blue-700' => request()->routeIs('recipes*'),
                        'font-medium text-slate-600 hover:bg-slate-50 hover:text-blue-700' => ! request()->routeIs('recipes*'),
                    ])>
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500" aria-hidden="true">R</span>
                        Receitas
                    </a>
                </div>

                <div class="space-y-1">
                    <p class="mb-3 px-3 text-xs font-semibold uppercase tracking-widest text-slate-400">Operação</p>
                    <a href="{{ route('productions.index') }}" @class([
                        'flex items-center gap-3 rounded-xl px-3 py-3 text-sm no-underline transition',
                        'bg-blue-50 font-semibold text-blue-700' => request()->routeIs('productions*'),
                        'font-medium text-slate-600 hover:bg-slate-50 hover:text-blue-700' => ! request()->routeIs('productions*'),
                    ])>
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500" aria-hidden="true">F</span>
                        Produção
                    </a>
                    <a href="{{ route('orders.index') }}" @class([
                        'flex items-center gap-3 rounded-xl px-3 py-3 text-sm no-underline transition',
                        'bg-blue-50 font-semibold text-blue-700' => request()->routeIs('orders*'),
                        'font-medium text-slate-600 hover:bg-slate-50 hover:text-blue-700' => ! request()->routeIs('orders*'),
                    ])>
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500" aria-hidden="true">V</span>
                        Pedidos e vendas
                    </a>
                    <a href="{{ route('stock-adjustments.index') }}" @class([
                        'flex items-center gap-3 rounded-xl px-3 py-3 text-sm no-underline transition',
                        'bg-blue-50 font-semibold text-blue-700' => request()->routeIs('stock-adjustments*'),
                        'font-medium text-slate-600 hover:bg-slate-50 hover:text-blue-700' => ! request()->routeIs('stock-adjustments*'),
                    ])>
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500" aria-hidden="true">±</span>
                        Movimentações de estoque
                    </a>
                </div>

                <div class="space-y-1">
                    <p class="mb-3 px-3 text-xs font-semibold uppercase tracking-widest text-slate-400">Relatórios e acesso</p>
                    <a href="{{ route('reports.production') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-slate-600 no-underline transition hover:bg-slate-50 hover:text-blue-700">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500" aria-hidden="true">R</span>
                        Relatório de produção
                    </a>
                    <a href="{{ route('reports.ingredient-consumption') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-slate-600 no-underline transition hover:bg-slate-50 hover:text-blue-700">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500" aria-hidden="true">C</span>
                        Consumo de ingredientes
                    </a>
                    @if (in_array(auth()->user()->role, ['admin', 'gerente'], true))
                        <a href="{{ route('users') }}" @class([
                            'flex items-center gap-3 rounded-xl px-3 py-3 text-sm no-underline transition',
                            'bg-blue-50 font-semibold text-blue-700' => request()->routeIs('users*'),
                            'font-medium text-slate-600 hover:bg-slate-50 hover:text-blue-700' => ! request()->routeIs('users*'),
                        ])>
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500" aria-hidden="true">U</span>
                            Usuários
                        </a>
                    @endif
                </div>
            </nav>
        </aside>

        <main class="min-h-screen pt-20 lg:ml-72">
            <div class="mx-auto max-w-7xl p-4 sm:p-6 lg:p-10">
                @if (session('success'))
                    <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="alert">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                        <p class="mb-1 font-semibold">Revise os campos indicados:</p>
                        <ul class="mb-0 list-inside list-disc">
                            @foreach ($errors->all() as $erro)
                                <li>{{ $erro }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('conteudo')
            </div>
        </main>

        <script>
            (() => {
                const botao = document.getElementById('menu-lateral-botao');
                const menu = document.getElementById('menu-lateral');
                const fundo = document.getElementById('menu-lateral-fundo');

                const fecharMenu = () => {
                    menu.classList.add('-translate-x-full');
                    fundo.classList.add('hidden');
                    botao.setAttribute('aria-expanded', 'false');
                };

                botao.addEventListener('click', () => {
                    const aberto = botao.getAttribute('aria-expanded') === 'true';
                    menu.classList.toggle('-translate-x-full', aberto);
                    fundo.classList.toggle('hidden', aberto);
                    botao.setAttribute('aria-expanded', String(! aberto));
                });

                fundo.addEventListener('click', fecharMenu);
                menu.querySelectorAll('a').forEach((link) => link.addEventListener('click', fecharMenu));
            })();
        </script>
        @stack('scripts')
    </body>
</html>
