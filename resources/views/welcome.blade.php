<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Sign in · {{ config('app.name', 'Recipe Tool') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
        <main class="flex min-h-screen items-center justify-center px-6 py-12">
            <div class="grid w-full max-w-5xl overflow-hidden rounded-3xl bg-white shadow-xl shadow-blue-950/10 lg:grid-cols-2">
                <section class="relative hidden overflow-hidden bg-blue-700 p-12 text-white lg:flex lg:flex-col lg:justify-between">
                    <div class="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-blue-500/50"></div>
                    <div class="absolute -bottom-28 -left-20 h-72 w-72 rounded-full bg-blue-800/60"></div>

                    <div class="relative">
                        <div class="mb-10 flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15 text-xl font-bold ring-1 ring-white/25">
                                R
                            </div>
                            <span class="text-lg font-semibold tracking-tight">Recipe Tool</span>
                        </div>

                        <p class="max-w-sm text-4xl font-semibold leading-tight tracking-tight">
                            Bring every recipe and ingredient together.
                        </p>
                    </div>

                    <p class="relative max-w-sm text-sm leading-6 text-blue-100">
                        Manage the ingredients that make your products, all in one clear and simple workspace.
                    </p>
                </section>

                <section class="p-8 sm:p-12 lg:p-16">
                    <div class="mx-auto max-w-md">
                        <div class="mb-10 lg:hidden">
                            <div class="mb-6 flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-700 text-xl font-bold text-white">
                                    R
                                </div>
                                <span class="text-lg font-semibold tracking-tight">Recipe Tool</span>
                            </div>
                        </div>

                        <div class="mb-8">
                            <p class="mb-3 text-sm font-semibold uppercase tracking-widest text-blue-700">Welcome back</p>
                            <h1 class="text-3xl font-semibold tracking-tight text-slate-950">Sign in to your workspace</h1>
                            <p class="mt-3 text-sm leading-6 text-slate-500">Enter your details to continue managing your recipes.</p>
                        </div>

                        @if ($errors->any())
                            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <form action="{{ route('login.store') }}" method="POST" class="space-y-5">
                            @csrf

                            <div>
                                <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Email address</label>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    value="{{ old('email') }}"
                                    autocomplete="email"
                                    required
                                    autofocus
                                    class="block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
                                    placeholder="you@example.com"
                                >
                                @error('email')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <div class="mb-2 flex items-center justify-between">
                                    <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                                    <span class="text-xs text-slate-400">Keep it secure</span>
                                </div>
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    autocomplete="current-password"
                                    required
                                    class="block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
                                    placeholder="Your password"
                                >
                                @error('password')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <label class="flex items-center gap-3 text-sm text-slate-600">
                                <input name="remember" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-blue-700 focus:ring-blue-500">
                                Remember me
                            </label>

                            <button type="submit" class="w-full rounded-xl bg-blue-700 px-4 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-700/20 transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200">
                                Sign in
                            </button>
                        </form>
                    </div>
                </section>
            </div>
        </main>
    </body>
</html>
