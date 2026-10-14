@extends('layouts.app')

@section('titulo_pagina', 'Ajustar estoque')

@section('conteudo')
    <div class="mb-6">
        <a href="{{ route('stock-adjustments.index') }}" class="text-sm font-medium text-blue-700 no-underline hover:text-blue-900">← Movimentações</a>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Ajuste manual de estoque</h1>
        <p class="mt-2 text-sm text-slate-500">Informe o item, o tipo de movimento, a quantidade e a justificativa.</p>
    </div>

    @php
        $ajustavelSelecionado = old('ajustavel_type') && old('ajustavel_id')
            ? old('ajustavel_type').'|'.old('ajustavel_id')
            : '';
    @endphp
    <form action="{{ route('stock-adjustments.store') }}" method="POST" class="max-w-4xl rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8" id="form-ajuste-estoque">
        @csrf
        <input type="hidden" name="ajustavel_type" id="ajustavel_type" value="{{ old('ajustavel_type') }}">
        <input type="hidden" name="ajustavel_id" id="ajustavel_id" value="{{ old('ajustavel_id') }}">
        <div class="grid gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <label for="ajustavel_selecao" class="block text-sm font-medium text-slate-700">Ingrediente ou produto</label>
                <select id="ajustavel_selecao" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 @error('ajustavel_id') border-red-500 @enderror @error('ajustavel_type') border-red-500 @enderror">
                    <option value="">Selecione um item</option>
                    <optgroup label="Ingredientes">
                        @foreach ($ingredientes as $ingrediente)
                            <option value="{{ App\Models\Ingrediente::class }}|{{ $ingrediente->id }}" @selected($ajustavelSelecionado === App\Models\Ingrediente::class.'|'.$ingrediente->id)>{{ $ingrediente->nome }} — {{ number_format((float) $ingrediente->quantidade_estoque, 3, ',', '.') }} {{ $ingrediente->unidade_medida }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Produtos">
                        @foreach ($produtos as $produto)
                            <option value="{{ App\Models\Produto::class }}|{{ $produto->id }}" @selected($ajustavelSelecionado === App\Models\Produto::class.'|'.$produto->id)>{{ $produto->nome }} — estoque {{ number_format((float) $produto->quantidade_estoque, 3, ',', '.') }}</option>
                        @endforeach
                    </optgroup>
                </select>
                @error('ajustavel_id')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                @error('ajustavel_type')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="tipo_ajuste" class="block text-sm font-medium text-slate-700">Tipo de ajuste</label>
                <select id="tipo_ajuste" name="tipo_ajuste" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 @error('tipo_ajuste') border-red-500 @enderror">
                    <option value="">Selecione</option>
                    <option value="entrada" @selected(old('tipo_ajuste') === 'entrada')>Entrada</option>
                    <option value="saida" @selected(old('tipo_ajuste') === 'saida')>Saída</option>
                </select>
                @error('tipo_ajuste')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="quantidade" class="block text-sm font-medium text-slate-700">Quantidade</label>
                <input id="quantidade" name="quantidade" type="number" min="0.001" step="0.001" value="{{ old('quantidade') }}" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 @error('quantidade') border-red-500 @enderror">
                @error('quantidade')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="data_ajuste" class="block text-sm font-medium text-slate-700">Data e hora</label>
                <input id="data_ajuste" name="data_ajuste" type="datetime-local" value="{{ old('data_ajuste', now()->format('Y-m-d\TH:i')) }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 @error('data_ajuste') border-red-500 @enderror">
                @error('data_ajuste')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label for="justificativa" class="block text-sm font-medium text-slate-700">Justificativa obrigatória</label>
                <textarea id="justificativa" name="justificativa" rows="3" maxlength="255" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 @error('justificativa') border-red-500 @enderror">{{ old('justificativa') }}</textarea>
                @error('justificativa')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="mt-8 flex flex-wrap gap-3 border-t border-slate-100 pt-5">
            <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">Registrar ajuste</button>
            <a href="{{ route('stock-adjustments.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 no-underline transition hover:bg-slate-50">Cancelar</a>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.getElementById('form-ajuste-estoque').addEventListener('submit', (evento) => {
            const [tipo, id] = document.getElementById('ajustavel_selecao').value.split('|');

            if (!tipo || !id) {
                evento.preventDefault();
                document.getElementById('ajustavel_selecao').classList.add('border-red-500');
                return;
            }

            document.getElementById('ajustavel_type').value = tipo;
            document.getElementById('ajustavel_id').value = id;
        });
    </script>
@endpush
