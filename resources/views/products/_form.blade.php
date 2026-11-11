<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label for="nome" class="block text-sm font-medium text-slate-700">Nome</label>
        <input id="nome" name="nome" value="{{ old('nome', $produto->nome ?? '') }}" required maxlength="255" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 @error('nome') border-red-500 @enderror">
        @error('nome')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="preco_venda" class="block text-sm font-medium text-slate-700">Preço de venda (R$)</label>
        <input id="preco_venda" name="preco_venda" type="number" min="0" step="0.01" value="{{ old('preco_venda', $produto->preco_venda ?? '') }}" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 @error('preco_venda') border-red-500 @enderror">
        @error('preco_venda')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    @unless (isset($produto))
        <div>
            <label for="quantidade_estoque" class="block text-sm font-medium text-slate-700">Saldo inicial</label>
            <input id="quantidade_estoque" name="quantidade_estoque" type="number" min="0" step="1" value="{{ old('quantidade_estoque', '0') }}" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 @error('quantidade_estoque') border-red-500 @enderror">
            @error('quantidade_estoque')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
    @endunless
    <div class="md:col-span-2">
        <label for="descricao" class="block text-sm font-medium text-slate-700">Descrição</label>
        <textarea id="descricao" name="descricao" rows="3" required maxlength="255" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 @error('descricao') border-red-500 @enderror">{{ old('descricao', $produto->descricao ?? '') }}</textarea>
        @error('descricao')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
</div>
