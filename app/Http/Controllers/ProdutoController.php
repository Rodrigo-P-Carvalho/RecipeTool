<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProdutoController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $search = trim($validated['search'] ?? '');

        $produtos = Produto::query()
            ->when(
                $search !== '',
                fn ($query) => $query->whereLike('nome', "%{$search}%"),
            )
            ->orderBy('nome')
            ->paginate(20)
            ->withQueryString();

        return view('products', compact('produtos', 'search'));
    }

    public function create(): View
    {
        return view('products.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'quantidade_estoque' => ['required', 'integer', 'min:0'],
            'descricao' => ['required', 'string', 'max:255'],
            'preco_venda' => ['required', 'numeric', 'min:0'],
        ]);

        $produto = DB::transaction(function () use ($attributes): Produto {
            $produto = Produto::query()->create($attributes);

            if ((float) $produto->quantidade_estoque > 0) {
                $produto->ajustesEstoque()->create([
                    'usuario_id' => Auth::id(),
                    'data_ajuste' => now(),
                    'tipo_ajuste' => 'cadastro_entrada',
                    'quantidade' => $produto->quantidade_estoque,
                    'justificativa' => 'Saldo inicial informado no cadastro do produto.',
                ]);
            }

            return $produto;
        });

        return redirect()->route('products.show', $produto)
            ->with('success', 'Produto cadastrado com sucesso.');
    }

    public function show(Produto $produto): View
    {
        return view('products.show', compact('produto'));
    }

    public function edit(Produto $produto): View
    {
        return view('products.edit', compact('produto'));
    }

    public function update(Request $request, Produto $produto): RedirectResponse
    {
        $attributes = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'descricao' => ['required', 'string', 'max:255'],
            'preco_venda' => ['required', 'numeric', 'min:0'],
        ]);

        $produto->update($attributes);

        return redirect()->route('products.show', $produto)
            ->with('success', 'Produto atualizado com sucesso.');
    }

    public function destroy(Produto $produto): RedirectResponse
    {
        if (
            $produto->itensPedido()->exists()
            || $produto->ajustesEstoque()->exists()
            || $produto->receita()->whereHas('producoes')->exists()
        ) {
            return back()->withErrors([
                'produto' => 'Não é possível excluir um produto usado em pedidos, produções ou no histórico de estoque.',
            ]);
        }

        $produto->delete();

        return redirect()->route('products')->with('success', 'Produto excluído com sucesso.');
    }
}
