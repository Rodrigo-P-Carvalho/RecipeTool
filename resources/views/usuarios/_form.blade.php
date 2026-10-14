<div class="space-y-5">
    <div>
        <label for="name" class="mb-2 block text-sm font-medium text-slate-700">Nome</label>
        <input id="name" name="name" type="text" value="{{ old('name', $usuario->name ?? '') }}" required maxlength="255" autocomplete="name" class="block w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
        @error('name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="email" class="mb-2 block text-sm font-medium text-slate-700">E-mail</label>
        <input id="email" name="email" type="email" value="{{ old('email', $usuario->email ?? '') }}" required maxlength="255" autocomplete="email" class="block w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
        @error('email')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="role" class="mb-2 block text-sm font-medium text-slate-700">Nível de acesso</label>
        <select id="role" name="role" required class="block w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
            @foreach ($roles as $valor => $rotulo)
                <option value="{{ $valor }}" @selected(old('role', $usuario->role ?? 'funcionario') === $valor)>{{ $rotulo }}</option>
            @endforeach
        </select>
        @error('role')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    @if (! isset($usuario))
        <p class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-blue-900">
            Uma senha provisória será gerada e registrada no log do sistema. No primeiro acesso, o usuário deverá criar uma nova senha.
        </p>
    @endif

    <div class="flex flex-wrap gap-3 pt-2">
        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800">{{ $botao }}</button>
        <a href="{{ route('users') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 no-underline transition hover:bg-slate-50">Cancelar</a>
    </div>
</div>
