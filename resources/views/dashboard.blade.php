<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Dashboard · {{ config('app.name', 'Recipe Tool') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
        <main class="mx-auto max-w-7xl px-6 py-12">
            <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Recipe Tool</p>
            <p class="mt-3 text-3xl font-semibold tracking-tight">
                You are logged in as {{ auth()->user()->name }} ({{ auth()->user()->email }}).
            </p>
            <p class="mt-3 text-slate-500">Your recipe workspace is ready.</p>

            <form action="{{ route('logout') }}" method="POST" class="mt-8">
                @csrf
                <button type="submit" class="rounded-xl bg-blue-700 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-800">
                    Log out
                </button>
            </form>
        </main>
    </body>
</html>
