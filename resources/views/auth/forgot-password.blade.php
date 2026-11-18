<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Esqueci minha senha · {{ config('app.name', 'RecipeTool') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-10 text-slate-900 antialiased">
        <main class="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-8 shadow-xl shadow-blue-950/5 sm:p-10">
            <a href="{{ route('login') }}" class="text-sm font-medium text-blue-700 no-underline hover:text-blue-900">← Voltar para entrar</a>
            <p class="mt-8 text-sm font-semibold uppercase tracking-widest text-blue-700">Recuperação de acesso</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">Esqueci minha senha</h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">Informe o e-mail associado à sua conta. Se ela existir, enviaremos um link para criar uma nova senha.</p>

            @if (session('status'))
                <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('password.email') }}" method="POST" class="mt-7 space-y-5">
                @csrf
                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Endereço de e-mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus class="block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100" placeholder="voce@exemplo.com">
                </div>
                <button type="submit" class="w-full rounded-xl bg-blue-700 px-4 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-700/20 transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200">
                    Enviar link de redefinição
                </button>
            </form>
        </main>
    </body>
</html>
