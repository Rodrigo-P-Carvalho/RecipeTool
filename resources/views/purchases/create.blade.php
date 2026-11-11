@extends('layouts.app')

@section('titulo_pagina', 'Registrar compra')

@section('conteudo')
    <div class="mb-6">
        <h1 class="text-3xl font-semibold tracking-tight text-slate-950">Registrar compra</h1>
        <p class="mt-2 text-sm text-slate-500">Registre os ingredientes recebidos, seus custos totais e as datas de validade dos lotes.</p>
    </div>

    @if ($ingredientes->isEmpty())
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="status">
            Não há ingredientes ativos para adicionar a uma compra.
        </div>
    @else
        <form action="{{ route('purchases.store') }}" method="POST" class="space-y-6">
            @csrf
            <section class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-2">
                <div>
                    <label for="data_compra" class="mb-1 block text-sm font-medium text-slate-700">Data da compra</label>
                    <input id="data_compra" name="data_compra" type="date" value="{{ old('data_compra', now()->toDateString()) }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('data_compra') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="observacoes" class="mb-1 block text-sm font-medium text-slate-700">Observações</label>
                    <input id="observacoes" name="observacoes" type="text" value="{{ old('observacoes') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('observacoes') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
            </section>

            <section class="space-y-4" aria-labelledby="purchase-items-heading">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 id="purchase-items-heading" class="text-xl font-semibold text-slate-900">Itens da compra</h2>
                        <p class="mt-1 text-sm text-slate-500">Informe a quantidade na unidade de medida do ingrediente e o preço total deste lote.</p>
                    </div>
                    <button id="add-purchase-item" type="button" class="rounded-lg border border-blue-200 bg-white px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50">Adicionar ingrediente</button>
                </div>

                <div id="purchase-items" class="space-y-4">
                    @php($purchaseItems = old('itens', [[]]))
                    @foreach ($purchaseItems as $index => $item)
                        <article class="purchase-item rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <div>
                                    <label for="ingredient-{{ $index }}" class="mb-1 block text-sm font-medium text-slate-700">Ingrediente</label>
                                    <select id="ingredient-{{ $index }}" name="itens[{{ $index }}][ingrediente_id]" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                                        <option value="">Selecione um ingrediente</option>
                                        @foreach ($ingredientes as $ingrediente)
                                            <option value="{{ $ingrediente->id }}" data-unit="{{ $ingrediente->unidade_medida->value }}" @selected(($item['ingrediente_id'] ?? '') == $ingrediente->id)>
                                                {{ $ingrediente->nome }} ({{ $ingrediente->unidade_medida->value }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error("itens.{$index}.ingrediente_id") <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="quantity-{{ $index }}" class="mb-1 block text-sm font-medium text-slate-700">Quantidade recebida</label>
                                    <input id="quantity-{{ $index }}" name="itens[{{ $index }}][quantidade]" type="number" step="any" min="0.001" value="{{ $item['quantidade'] ?? '' }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                                    @error("itens.{$index}.quantidade") <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="cost-{{ $index }}" class="mb-1 block text-sm font-medium text-slate-700">Custo total do lote (R$)</label>
                                    <input id="cost-{{ $index }}" name="itens[{{ $index }}][preco_custo]" type="number" step="0.01" min="0" value="{{ $item['preco_custo'] ?? '' }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                                    @error("itens.{$index}.preco_custo") <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="expiry-{{ $index }}" class="mb-1 block text-sm font-medium text-slate-700">Data de validade</label>
                                    <input id="expiry-{{ $index }}" name="itens[{{ $index }}][data_validade]" type="date" value="{{ $item['data_validade'] ?? '' }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                                    @error("itens.{$index}.data_validade") <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="lot-code-{{ $index }}" class="mb-1 block text-sm font-medium text-slate-700">Código do lote (opcional)</label>
                                    <input id="lot-code-{{ $index }}" name="itens[{{ $index }}][codigo_lote]" type="text" value="{{ $item['codigo_lote'] ?? '' }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                                    @error("itens.{$index}.codigo_lote") <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                                </div>
                                <div class="flex items-end">
                                    <button type="button" class="remove-purchase-item rounded-lg px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50">Remover item</button>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">Registrar compra</button>
                <a href="{{ route('purchases.index') }}" class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 no-underline hover:bg-slate-50">Cancelar</a>
            </div>
        </form>

        <template id="purchase-item-template">
            <article class="purchase-item rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label for="ingredient-__INDEX__" class="mb-1 block text-sm font-medium text-slate-700">Ingrediente</label>
                        <select id="ingredient-__INDEX__" name="itens[__INDEX__][ingrediente_id]" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                            <option value="">Selecione um ingrediente</option>
                            @foreach ($ingredientes as $ingrediente)
                                <option value="{{ $ingrediente->id }}" data-unit="{{ $ingrediente->unidade_medida->value }}">{{ $ingrediente->nome }} ({{ $ingrediente->unidade_medida->value }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="quantity-__INDEX__" class="mb-1 block text-sm font-medium text-slate-700">Quantidade recebida</label>
                        <input id="quantity-__INDEX__" name="itens[__INDEX__][quantidade]" type="number" step="any" min="0.001" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    </div>
                    <div>
                        <label for="cost-__INDEX__" class="mb-1 block text-sm font-medium text-slate-700">Custo total do lote (R$)</label>
                        <input id="cost-__INDEX__" name="itens[__INDEX__][preco_custo]" type="number" step="0.01" min="0" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    </div>
                    <div>
                        <label for="expiry-__INDEX__" class="mb-1 block text-sm font-medium text-slate-700">Data de validade</label>
                        <input id="expiry-__INDEX__" name="itens[__INDEX__][data_validade]" type="date" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    </div>
                    <div>
                        <label for="lot-code-__INDEX__" class="mb-1 block text-sm font-medium text-slate-700">Código do lote (opcional)</label>
                        <input id="lot-code-__INDEX__" name="itens[__INDEX__][codigo_lote]" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    </div>
                    <div class="flex items-end">
                        <button type="button" class="remove-purchase-item rounded-lg px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50">Remover item</button>
                    </div>
                </div>
            </article>
        </template>
    @endif
@endsection

@push('scripts')
    <script>
        (() => {
            const list = document.getElementById('purchase-items');
            const addButton = document.getElementById('add-purchase-item');
            const template = document.getElementById('purchase-item-template');

            if (!list || !addButton || !template) {
                return;
            }

            let nextIndex = list.querySelectorAll('.purchase-item').length;

            const updateQuantityStep = (select) => {
                const quantity = select.closest('.purchase-item').querySelector('input[name$="[quantidade]"]');
                const isWholeUnit = select.selectedOptions[0]?.dataset.unit === 'un';
                quantity.step = isWholeUnit ? '1' : 'any';
                quantity.min = isWholeUnit ? '1' : '0.001';
            };

            list.querySelectorAll('select[name$="[ingrediente_id]"]').forEach((select) => {
                updateQuantityStep(select);
                select.addEventListener('change', () => updateQuantityStep(select));
            });

            addButton.addEventListener('click', () => {
                const item = template.content.cloneNode(true);
                item.querySelectorAll('[name], [id], [for]').forEach((element) => {
                    ['name', 'id', 'for'].forEach((attribute) => {
                        if (element.hasAttribute(attribute)) {
                            element.setAttribute(attribute, element.getAttribute(attribute).replaceAll('__INDEX__', String(nextIndex)));
                        }
                    });
                });
                list.append(item);
                const select = list.lastElementChild.querySelector('select[name$="[ingrediente_id]"]');
                select.addEventListener('change', () => updateQuantityStep(select));
                nextIndex += 1;
            });

            list.addEventListener('click', (event) => {
                if (event.target.closest('.remove-purchase-item') && list.querySelectorAll('.purchase-item').length > 1) {
                    event.target.closest('.purchase-item').remove();
                }
            });
        })();
    </script>
@endpush
