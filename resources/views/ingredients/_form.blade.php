<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label for="nome" class="block text-sm font-medium text-slate-700">Nome</label>
        <input id="nome" name="nome" value="{{ old('nome', $ingrediente->nome ?? '') }}" required maxlength="255" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 @error('nome') border-red-500 @enderror">
        @error('nome')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="unidade_medida" class="block text-sm font-medium text-slate-700">Unidade para estoque e receitas</label>
        <select id="unidade_medida" name="unidade_medida" data-tipo-fixo="{{ isset($ingrediente) ? $ingrediente->unidade_medida->tipo() : '' }}" data-unidade-original="{{ isset($ingrediente) ? $ingrediente->unidade_medida->value : '' }}" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 @error('unidade_medida') border-red-500 @enderror">
            @foreach ($unidadesMedida as $unidade)
                <option value="{{ $unidade->value }}" data-tipo="{{ $unidade->tipo() }}" @selected(old('unidade_medida', isset($ingrediente) ? $ingrediente->unidade_medida->value : 'g') === $unidade->value)>{{ $unidade->value }}</option>
            @endforeach
        </select>
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
            <label for="quantidade_inicial" class="block text-sm font-medium text-slate-700">Saldo inicial (<span data-unidade-medida-label>{{ old('unidade_medida', 'g') }}</span>)</label>
            <input id="quantidade_inicial" name="quantidade_inicial" type="number" min="0" step="0.001" value="{{ old('quantidade_inicial', '0') }}" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 @error('quantidade_inicial') border-red-500 @enderror">
            @error('quantidade_inicial')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
    @endunless
    <div>
        <label for="estoque_minimo" class="block text-sm font-medium text-slate-700">Estoque mínimo (<span data-unidade-medida-label>{{ old('unidade_medida', isset($ingrediente) ? $ingrediente->unidade_medida->value : 'g') }}</span>)</label>
        <input id="estoque_minimo" name="estoque_minimo" type="number" min="0" step="0.001" value="{{ old('estoque_minimo', isset($ingrediente) ? $ingrediente->estoque_minimo_na_unidade : '0') }}" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 @error('estoque_minimo') border-red-500 @enderror">
        @error('estoque_minimo')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="tamanho_embalagem" class="block text-sm font-medium text-slate-700">Tamanho da embalagem (<span data-unidade-embalagem-label>{{ old('unidade_embalagem', isset($ingrediente) ? $ingrediente->unidade_embalagem->value : old('unidade_medida', 'g')) }}</span>)</label>
        <input id="tamanho_embalagem" name="tamanho_embalagem" type="number" min="0.001" step="0.001" value="{{ old('tamanho_embalagem', $ingrediente->tamanho_embalagem ?? '') }}" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 @error('tamanho_embalagem') border-red-500 @enderror">
        @error('tamanho_embalagem')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="unidade_embalagem" class="block text-sm font-medium text-slate-700">Unidade da embalagem</label>
        <select id="unidade_embalagem" name="unidade_embalagem" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 @error('unidade_embalagem') border-red-500 @enderror">
            @foreach ($unidadesMedida as $unidade)
                <option value="{{ $unidade->value }}" data-tipo="{{ $unidade->tipo() }}" @selected(old('unidade_embalagem', isset($ingrediente) ? $ingrediente->unidade_embalagem->value : old('unidade_medida', 'g')) === $unidade->value)>{{ $unidade->value }}</option>
            @endforeach
        </select>
        @error('unidade_embalagem')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="preco_embalagem" class="block text-sm font-medium text-slate-700">Preço da embalagem (R$)</label>
        <input id="preco_embalagem" name="preco_embalagem" type="number" min="0" step="0.01" value="{{ old('preco_embalagem', $ingrediente->preco_embalagem ?? '') }}" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 @error('preco_embalagem') border-red-500 @enderror">
        @error('preco_embalagem')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
</div>

@pushOnce('scripts', 'ingredient-unit-labels')
    <script>
        (() => {
            const unidadeMedida = document.getElementById('unidade_medida');
            const unidadeEmbalagem = document.getElementById('unidade_embalagem');

            const atualizarRotulos = () => {
                const tipoFixo = unidadeMedida.dataset.tipoFixo;

                if (tipoFixo !== '' && unidadeMedida.selectedOptions[0].dataset.tipo !== tipoFixo) {
                    unidadeMedida.value = unidadeMedida.dataset.unidadeOriginal;
                }

                const tipo = unidadeMedida.selectedOptions[0].dataset.tipo;
                const unidadeEmbalagemIncompativel = unidadeEmbalagem.selectedOptions[0]?.dataset.tipo !== tipo;

                unidadeMedida.querySelectorAll('option[data-tipo]').forEach((opcao) => {
                    opcao.disabled = tipoFixo !== '' && opcao.dataset.tipo !== tipoFixo;
                });

                unidadeEmbalagem.querySelectorAll('option[data-tipo]').forEach((opcao) => {
                    opcao.disabled = opcao.dataset.tipo !== tipo;
                });

                if (unidadeEmbalagemIncompativel) {
                    unidadeEmbalagem.value = unidadeMedida.value;
                }

                document.querySelectorAll('[data-unidade-medida-label]').forEach((rotulo) => {
                    rotulo.textContent = unidadeMedida.value;
                });
                document.querySelectorAll('[data-unidade-embalagem-label]').forEach((rotulo) => {
                    rotulo.textContent = unidadeEmbalagem.value;
                });
            };

            unidadeMedida.addEventListener('change', atualizarRotulos);
            unidadeEmbalagem.addEventListener('change', atualizarRotulos);
            atualizarRotulos();
        })();
    </script>
@endPushOnce
