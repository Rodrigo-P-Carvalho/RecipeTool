<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label for="nome" class="block text-sm font-medium text-slate-700">Nome</label>
        <input id="nome" name="nome" value="{{ old('nome', $ingrediente->nome ?? '') }}" required maxlength="255" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 @error('nome') border-red-500 @enderror">
        @error('nome')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="unidade_medida" class="block text-sm font-medium text-slate-700">Unidade de medida</label>
        <input id="unidade_medida" name="unidade_medida" value="{{ old('unidade_medida', $ingrediente->unidade_medida ?? '') }}" placeholder="g, ml, un..." required maxlength="50" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 @error('unidade_medida') border-red-500 @enderror">
        @error('unidade_medida')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="status_ingrediente" class="block text-sm font-medium text-slate-700">Status</label>
        <select id="status_ingrediente" name="status_ingrediente" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 @error('status_ingrediente') border-red-500 @enderror">
            @foreach (['ativo' => 'Ativo', 'inativo' => 'Inativo'] as $valor => $rotulo)
                <option value="{{ $valor }}" @selected(old('status_ingrediente', $ingrediente->status_ingrediente ?? 'ativo') === $valor)>{{ $rotulo }}</option>
            @endforeach
        </select>
        @error('status_ingrediente')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    @unless (isset($ingrediente))
        <div>
            <label for="quantidade_estoque" class="block text-sm font-medium text-slate-700">Saldo inicial</label>
            <input id="quantidade_estoque" name="quantidade_estoque" type="number" min="0" step="0.001" value="{{ old('quantidade_estoque', '0') }}" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 @error('quantidade_estoque') border-red-500 @enderror">
            @error('quantidade_estoque')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
    @endunless
    <div>
        <label for="estoque_minimo" class="block text-sm font-medium text-slate-700">Estoque mínimo</label>
        <input id="estoque_minimo" name="estoque_minimo" type="number" min="0" step="0.001" value="{{ old('estoque_minimo', $ingrediente->estoque_minimo ?? '0') }}" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 @error('estoque_minimo') border-red-500 @enderror">
        @error('estoque_minimo')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="preco_embalagem" class="block text-sm font-medium text-slate-700">Preço da embalagem (R$)</label>
        <input id="preco_embalagem" name="preco_embalagem" type="number" min="0" step="0.01" value="{{ old('preco_embalagem', $ingrediente->preco_embalagem ?? '') }}" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 @error('preco_embalagem') border-red-500 @enderror">
        @error('preco_embalagem')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="tamanho_embalagem" class="block text-sm font-medium text-slate-700">Tamanho da embalagem ({{ old('unidade_medida', $ingrediente->unidade_medida ?? 'unidade') }})</label>
        <input id="tamanho_embalagem" name="tamanho_embalagem" type="number" min="0.001" step="0.001" value="{{ old('tamanho_embalagem', $ingrediente->tamanho_embalagem ?? '') }}" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 @error('tamanho_embalagem') border-red-500 @enderror">
        @error('tamanho_embalagem')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
</div>
