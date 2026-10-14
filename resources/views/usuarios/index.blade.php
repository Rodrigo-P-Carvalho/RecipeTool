@extends('layouts.app')

@section('titulo_pagina', 'Usuários')

@section('conteudo')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-950">Gerenciar usuários</h1>
            <p class="mt-2 text-sm text-slate-500">Cadastre contas e defina os níveis de acesso ao RecipeTool.</p>
        </div>
        <a href="{{ route('users.create') }}" class="inline-flex items-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white no-underline shadow-sm transition hover:bg-blue-800">Cadastrar usuário</a>
    </div>

    <form action="{{ route('users') }}" method="GET" role="search" class="mb-5 flex flex-col gap-3 sm:flex-row">
        <div class="flex-1">
            <label for="user-search" class="sr-only">Buscar usuário por nome ou e-mail</label>
            <input id="user-search" type="search" name="search" value="{{ $search }}" placeholder="Buscar por nome ou e-mail..." class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm outline-none placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">Buscar</button>
            @if ($search !== '')
                <a href="{{ route('users') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 no-underline transition hover:bg-slate-50">Limpar</a>
            @endif
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Nome</th>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">E-mail</th>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Nível de acesso</th>
                        <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($usuarios as $usuario)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-900">{{ $usuario->name }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $usuario->email }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">
                                <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-800">{{ \App\Models\User::ROLES[$usuario->role] ?? $usuario->role }}</span>
                                @if ($usuario->precisa_trocar_senha)
                                    <span class="ml-1 inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Primeiro acesso pendente</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right text-sm">
                                <a href="{{ route('users.edit', $usuario) }}" class="font-medium text-blue-700 no-underline hover:text-blue-900">Editar</a>
                                @if (! auth()->user()->is($usuario))
                                    <form action="{{ route('users.destroy', $usuario) }}" method="POST" class="ml-3 inline" onsubmit="return confirm('Tem certeza de que deseja excluir este usuário?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="font-medium text-red-700 hover:text-red-900">Excluir</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-12 text-center text-sm text-slate-500">{{ $search !== '' ? "Nenhum usuário encontrado para \"{$search}\"." : 'Nenhum usuário cadastrado.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $usuarios->links() }}</div>
@endsection
