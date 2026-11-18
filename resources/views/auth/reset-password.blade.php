<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Redefinir senha · {{ config('app.name', 'RecipeTool') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-10 text-slate-900 antialiased">
        <main class="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-8 shadow-xl shadow-blue-950/5 sm:p-10">
            <a href="{{ route('login') }}" class="text-sm font-medium text-blue-700 no-underline hover:text-blue-900">← Voltar para entrar</a>
            <p class="mt-8 text-sm font-semibold uppercase tracking-widest text-blue-700">Recuperação de acesso</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">Crie uma nova senha</h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">Escolha uma senha com pelo menos 8 caracteres, incluindo letras e números.</p>

            @if ($errors->any())
                <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('password.update') }}" method="POST" class="mt-7 space-y-5" data-password-reset-form>
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Endereço de e-mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" required class="block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                </div>
                <div>
                    <label for="password" class="mb-2 block text-sm font-medium text-slate-700">Nova senha</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required class="block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                </div>
                <div>
                    <label for="password_confirmation" class="mb-2 block text-sm font-medium text-slate-700">Confirme a nova senha</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required class="block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                </div>
                <ul id="password-reset-requirements" class="space-y-2 text-sm text-slate-500" aria-label="Requisitos da nova senha">
                    <li class="flex items-center gap-2" data-password-requirement="length">
                        <span aria-hidden="true" data-requirement-status>○</span>
                        <span>Pelo menos 8 caracteres</span>
                    </li>
                    <li class="flex items-center gap-2" data-password-requirement="letters">
                        <span aria-hidden="true" data-requirement-status>○</span>
                        <span>Pelo menos uma letra</span>
                    </li>
                    <li class="flex items-center gap-2" data-password-requirement="numbers">
                        <span aria-hidden="true" data-requirement-status>○</span>
                        <span>Pelo menos um número</span>
                    </li>
                    <li class="flex items-center gap-2" data-password-requirement="confirmation">
                        <span aria-hidden="true" data-requirement-status>○</span>
                        <span>Preencha a confirmação da senha</span>
                    </li>
                    <li class="flex items-center gap-2" data-password-requirement="match">
                        <span aria-hidden="true" data-requirement-status>○</span>
                        <span>As senhas devem corresponder</span>
                    </li>
                </ul>
                <button type="submit" class="w-full rounded-xl bg-blue-700 px-4 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-700/20 transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200">
                    Salvar nova senha
                </button>
            </form>
        </main>
    </body>
</html>
