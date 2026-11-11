@extends('layouts.app')

@section('titulo_pagina', 'Novo pedido')

@section('conteudo')
    <div class="mb-6">
        <a href="{{ route('orders.index') }}" class="text-sm font-medium text-blue-700 no-underline hover:text-blue-900">← Pedidos e vendas</a>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Registrar pedido</h1>
        <p class="mt-2 text-sm text-slate-500">O estoque disponível será reservado ao registrar o pedido.</p>
    </div>

    <form action="{{ route('orders.store') }}" method="POST" class="max-w-4xl rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
        @csrf
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-slate-900">Produtos do pedido</h2>
        </div>

        <div id="itens-pedido" class="grid gap-4">
            @foreach (old('itens', [['produto_id' => '', 'qtd' => 1]]) as $indice => $item)
                <div class="grid gap-3 rounded-xl border border-slate-200 p-4 sm:grid-cols-[minmax(0,1fr)_10rem_auto] sm:items-end" data-item-pedido data-index="{{ $indice }}">
                    <div>
                        <label for="itens-{{ $indice }}-produto" class="block text-sm font-medium text-slate-700">Produto</label>
                        <select id="itens-{{ $indice }}-produto" name="itens[{{ $indice }}][produto_id]" required class="produto-pedido mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <option value="">Selecione um produto</option>
                            @foreach ($produtos as $produto)
                                <option value="{{ $produto->id }}" data-preco="{{ $produto->preco_venda }}" data-estoque="{{ (int) $produto->quantidade_estoque }}" @selected((string) ($item['produto_id'] ?? '') === (string) $produto->id)>
                                    {{ $produto->nome }} — R$ {{ number_format((float) $produto->preco_venda, 2, ',', '.') }} ({{ (int) $produto->quantidade_estoque }} em estoque)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="itens-{{ $indice }}-qtd" class="block text-sm font-medium text-slate-700">Quantidade</label>
                        <input id="itens-{{ $indice }}-qtd" name="itens[{{ $indice }}][qtd]" type="number" min="1" step="1" value="{{ $item['qtd'] ?? 1 }}" required class="qtd-pedido mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                    <button type="button" data-remover-item class="rounded-lg px-3 py-2.5 text-sm font-medium text-red-700 transition hover:bg-red-50">Remover</button>
                </div>
            @endforeach
        </div>

        @if ($produtos->isEmpty())
            <p class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Não há produtos com saldo disponível para venda.</p>
        @endif

        <button type="button" id="adicionar-item-pedido" class="mt-4 rounded-lg border border-blue-200 px-3 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-50">Adicionar produto</button>
        <p id="aviso-produtos-esgotados" class="mt-3 hidden rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800" role="status" aria-live="polite"></p>

        <template id="modelo-item-pedido">
            <div class="grid gap-3 rounded-xl border border-slate-200 p-4 sm:grid-cols-[minmax(0,1fr)_10rem_auto] sm:items-end" data-item-pedido>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Produto</label>
                    <select name="itens[__INDEX__][produto_id]" required class="produto-pedido mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="">Selecione um produto</option>
                        @foreach ($produtos as $produto)
                            <option value="{{ $produto->id }}" data-preco="{{ $produto->preco_venda }}" data-estoque="{{ (int) $produto->quantidade_estoque }}">
                                {{ $produto->nome }} — R$ {{ number_format((float) $produto->preco_venda, 2, ',', '.') }} ({{ (int) $produto->quantidade_estoque }} em estoque)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Quantidade</label>
                    <input name="itens[__INDEX__][qtd]" type="number" min="1" step="1" value="1" required class="qtd-pedido mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>
                <button type="button" data-remover-item class="rounded-lg px-3 py-2.5 text-sm font-medium text-red-700 transition hover:bg-red-50">Remover</button>
            </div>
        </template>

        <div class="mt-6 flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 pt-5">
            <p class="text-sm text-slate-600">Total estimado: <strong id="total-estimado" class="text-lg text-slate-950">R$ 0,00</strong></p>
            <div class="flex gap-3">
                <button type="submit" @disabled($produtos->isEmpty()) class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:bg-slate-400">Registrar pedido</button>
                <a href="{{ route('orders.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 no-underline transition hover:bg-slate-50">Cancelar</a>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (() => {
            const lista = document.getElementById('itens-pedido');
            const modelo = document.getElementById('modelo-item-pedido');
            const botaoAdicionar = document.getElementById('adicionar-item-pedido');
            const totalEstimado = document.getElementById('total-estimado');
            const avisoProdutosEsgotados = document.getElementById('aviso-produtos-esgotados');
            let proximoIndice = Math.max(
                -1,
                ...Array.from(lista.querySelectorAll('[data-index]'), (linha) => Number(linha.dataset.index)),
            ) + 1;

            const atualizarOpcoesProduto = () => {
                const selecoes = Array.from(lista.querySelectorAll('.produto-pedido'));
                const produtosSelecionados = new Set(
                    selecoes.map((selecao) => selecao.value).filter(Boolean),
                );

                selecoes.forEach((selecao) => {
                    Array.from(selecao.options).forEach((opcao) => {
                        const selecionadoEmOutraLinha = Boolean(opcao.value)
                            && produtosSelecionados.has(opcao.value)
                            && opcao.value !== selecao.value;
                        opcao.hidden = selecionadoEmOutraLinha;
                        opcao.disabled = selecionadoEmOutraLinha;
                    });
                });
            };

            const atualizarTotal = () => {
                let total = 0;
                lista.querySelectorAll('[data-item-pedido]').forEach((linha) => {
                    const produto = linha.querySelector('.produto-pedido').selectedOptions[0];
                    const quantidade = Number(linha.querySelector('.qtd-pedido').value) || 0;
                    total += (Number(produto?.dataset.preco) || 0) * quantidade;
                });
                totalEstimado.textContent = total.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
            };

            const atualizarRemocao = () => {
                const linhas = lista.querySelectorAll('[data-item-pedido]');
                linhas.forEach((linha) => {
                    linha.querySelector('[data-remover-item]').disabled = linhas.length === 1;
                });
            };

            botaoAdicionar.addEventListener('click', () => {
                const produtosSelecionados = new Set(
                    Array.from(lista.querySelectorAll('.produto-pedido'))
                        .map((selecao) => selecao.value)
                        .filter(Boolean),
                );
                const existeProdutoDisponivel = Array.from(
                    modelo.content.querySelectorAll('.produto-pedido option'),
                ).some((opcao) => opcao.value && ! produtosSelecionados.has(opcao.value));

                if (! existeProdutoDisponivel) {
                    avisoProdutosEsgotados.textContent = 'Não há outros produtos disponíveis para adicionar ao pedido.';
                    avisoProdutosEsgotados.classList.remove('hidden');
                    return;
                }

                avisoProdutosEsgotados.textContent = '';
                avisoProdutosEsgotados.classList.add('hidden');
                const linha = modelo.content.firstElementChild.cloneNode(true);
                const indice = proximoIndice++;
                linha.dataset.index = String(indice);
                const selectProduto = linha.querySelector('.produto-pedido');
                const inputQuantidade = linha.querySelector('.qtd-pedido');
                selectProduto.name = `itens[${indice}][produto_id]`;
                selectProduto.id = `itens-${indice}-produto`;
                selectProduto.previousElementSibling.htmlFor = selectProduto.id;
                inputQuantidade.name = `itens[${indice}][qtd]`;
                inputQuantidade.id = `itens-${indice}-qtd`;
                inputQuantidade.previousElementSibling.htmlFor = inputQuantidade.id;
                lista.appendChild(linha);
                atualizarOpcoesProduto();
                atualizarRemocao();
                atualizarTotal();
            });

            lista.addEventListener('input', atualizarTotal);
            lista.addEventListener('change', (evento) => {
                if (evento.target.matches('.produto-pedido')) {
                    avisoProdutosEsgotados.textContent = '';
                    avisoProdutosEsgotados.classList.add('hidden');
                    atualizarOpcoesProduto();
                }
                atualizarTotal();
            });
            lista.addEventListener('click', (evento) => {
                const botaoRemover = evento.target.closest('[data-remover-item]');
                if (botaoRemover && lista.querySelectorAll('[data-item-pedido]').length > 1) {
                    botaoRemover.closest('[data-item-pedido]').remove();
                    atualizarOpcoesProduto();
                    atualizarOpcoesProduto();
                    atualizarRemocao();
                    atualizarTotal();
                }
            });

            atualizarRemocao();
            atualizarTotal();
        })();
    </script>
@endpush
