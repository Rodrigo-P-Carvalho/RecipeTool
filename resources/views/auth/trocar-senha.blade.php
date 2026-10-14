<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Trocar senha · {{ config('app.name', 'RecipeTool') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-10 text-slate-900 antialiased">
        <main class="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-8 shadow-xl shadow-blue-950/5 sm:p-10">
            <div class="mb-7 flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-2xl" aria-hidden="true">🔐</div>
            <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Primeiro acesso</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">Este é seu primeiro acesso</h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">Por segurança, você precisa criar uma nova senha antes de acessar o RecipeTool.</p>

            @if ($errors->any())
                <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $erro)
                            <li>{{ $erro }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('password.change.update') }}" method="POST" class="mt-7 space-y-5">
                @csrf
                @method('PUT')
                <div>
                    <label for="password" class="mb-2 block text-sm font-medium text-slate-700">Nova senha</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password" class="block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                    @error('password')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="mb-2 block text-sm font-medium text-slate-700">Confirme a nova senha</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                </div>
                <button type="submit" class="w-full rounded-xl bg-blue-700 px-4 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-700/20 transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200">Salvar nova senha</button>
            </form>

            <form action="{{ route('logout') }}" method="POST" class="mt-4 text-center">
                @csrf
                <button type="submit" class="text-sm font-medium text-slate-600 underline decoration-slate-300 underline-offset-4 hover:text-blue-700">Sair da conta</button>
            </form>
        </main>
    </body>
</html>
