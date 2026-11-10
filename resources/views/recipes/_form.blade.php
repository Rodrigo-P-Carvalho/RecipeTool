@php
    $linhasReceita = old('itens', isset($receita)
        ? $receita->itens->map(fn ($item) => [
            'ingrediente_id' => $item->ingrediente_id,
            'quantidade_necessaria' => $item->quantidade_necessaria_na_unidade,
        ])->all()
        : [['ingrediente_id' => '', 'quantidade_necessaria' => '']]);
@endphp

<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label for="produto_id" class="block text-sm font-medium text-slate-700">Produto final</label>
        <select id="produto_id" name="produto_id" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 @error('produto_id') border-red-500 @enderror">
            <option value="">Selecione um produto</option>
            @foreach ($produtos as $produto)
                <option value="{{ $produto->id }}" @selected((string) old('produto_id', $receita->produto_id ?? '') === (string) $produto->id)>{{ $produto->nome }}</option>
            @endforeach
        </select>
        @error('produto_id')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="descricao" class="block text-sm font-medium text-slate-700">Descrição</label>
        <input id="descricao" name="descricao" value="{{ old('descricao', $receita->descricao ?? '') }}" maxlength="255" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 @error('descricao') border-red-500 @enderror">
        @error('descricao')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
</div>

<div class="mt-8 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">Ingredientes e quantidades</h2>
        <p class="mt-1 text-sm text-slate-500">Informe a quantidade necessária para produzir um lote.</p>
    </div>
    <button type="button" class="rounded-lg border border-blue-200 px-3 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-50" id="adicionar-item">Adicionar ingrediente</button>
</div>

<div id="itens-receita" class="mt-4 space-y-3">
    @foreach ($linhasReceita as $indice => $linha)
        <div class="grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_auto]" data-item-row>
            <div>
                <label for="ingrediente_{{ $indice }}" class="block text-sm font-medium text-slate-700">Ingrediente</label>
                <select id="ingrediente_{{ $indice }}" name="itens[{{ $indice }}][ingrediente_id]" required data-ingredient-select class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">Selecione um ingrediente</option>
                    @foreach ($ingredientes as $ingrediente)
                        <option value="{{ $ingrediente->id }}" data-unidade="{{ $ingrediente->unidade_medida->value }}" @selected((string) ($linha['ingrediente_id'] ?? '') === (string) $ingrediente->id)>{{ $ingrediente->nome }} ({{ $ingrediente->unidade_medida->value }})</option>
                    @endforeach
                </select>
                @if ($errors->has('itens.'.$indice.'.ingrediente_id'))
                    <p class="mt-1 text-sm text-red-700">{{ $errors->first('itens.'.$indice.'.ingrediente_id') }}</p>
                @endif
            </div>
            <div>
                <label for="quantidade_{{ $indice }}" class="block text-sm font-medium text-slate-700">Quantidade (<span data-item-unit>{{ $ingredientes->firstWhere('id', $linha['ingrediente_id'] ?? null)?->unidade_medida->value }}</span>)</label>
                <input id="quantidade_{{ $indice }}" name="itens[{{ $indice }}][quantidade_necessaria]" type="number" min="0.000001" step="0.000001" value="{{ $linha['quantidade_necessaria'] ?? '' }}" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                @if ($errors->has('itens.'.$indice.'.quantidade_necessaria'))
                    <p class="mt-1 text-sm text-red-700">{{ $errors->first('itens.'.$indice.'.quantidade_necessaria') }}</p>
                @endif
            </div>
            <div class="flex items-end">
                <button type="button" class="w-full rounded-lg border border-red-200 px-3 py-2.5 text-sm font-medium text-red-700 transition hover:bg-red-50 sm:w-auto" data-remover-item>Remover</button>
            </div>
        </div>
    @endforeach
</div>
@error('itens')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror

<template id="modelo-item-receita">
    <div class="grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_auto]" data-item-row>
        <div>
            <label for="ingrediente___INDEX__" class="block text-sm font-medium text-slate-700">Ingrediente</label>
            <select id="ingrediente___INDEX__" name="itens[__INDEX__][ingrediente_id]" required data-ingredient-select class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                <option value="">Selecione um ingrediente</option>
                @foreach ($ingredientes as $ingrediente)
                    <option value="{{ $ingrediente->id }}" data-unidade="{{ $ingrediente->unidade_medida->value }}">{{ $ingrediente->nome }} ({{ $ingrediente->unidade_medida->value }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="quantidade___INDEX__" class="block text-sm font-medium text-slate-700">Quantidade (<span data-item-unit></span>)</label>
            <input id="quantidade___INDEX__" name="itens[__INDEX__][quantidade_necessaria]" type="number" min="0.000001" step="0.000001" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
        </div>
        <div class="flex items-end">
            <button type="button" class="w-full rounded-lg border border-red-200 px-3 py-2.5 text-sm font-medium text-red-700 transition hover:bg-red-50 sm:w-auto" data-remover-item>Remover</button>
        </div>
    </div>
</template>

@push('scripts')
    <script>
        (() => {
            const lista = document.getElementById('itens-receita');
            const modelo = document.getElementById('modelo-item-receita');
            let proximoIndice = {{ count($linhasReceita) }};

            const atualizarUnidadeDaLinha = (linha) => {
                const select = linha.querySelector('[data-ingredient-select]');
                const unidade = select.selectedOptions[0]?.dataset.unidade ?? '';

                linha.querySelector('[data-item-unit]').textContent = unidade;
            };

            lista.querySelectorAll('[data-item-row]').forEach((linha) => {
                atualizarUnidadeDaLinha(linha);
            });

            lista.addEventListener('change', (evento) => {
                if (evento.target.matches('[data-ingredient-select]')) {
                    atualizarUnidadeDaLinha(evento.target.closest('[data-item-row]'));
                }
            });

            document.getElementById('adicionar-item').addEventListener('click', () => {
                lista.insertAdjacentHTML('beforeend', modelo.innerHTML.replaceAll('__INDEX__', String(proximoIndice++)));
                atualizarUnidadeDaLinha(lista.lastElementChild);
            });

            lista.addEventListener('click', (evento) => {
                if (evento.target.closest('[data-remover-item]')) {
                    evento.target.closest('[data-item-row]').remove();
                }
            });
        })();
    </script>
@endpush
