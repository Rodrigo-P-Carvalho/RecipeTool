<?php

namespace App\Http\Controllers;

use App\Models\Ingrediente;
use App\Models\LoteIngrediente;
use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EstoqueController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'busca_ingrediente' => ['nullable', 'string', 'max:100'],
            'busca_produto' => ['nullable', 'string', 'max:100'],
        ]);
        $buscaIngrediente = trim($validated['busca_ingrediente'] ?? '');
        $buscaProduto = trim($validated['busca_produto'] ?? '');

        $ingredientes = Ingrediente::query()
            ->when(
                $buscaIngrediente !== '',
                fn ($query) => $query->whereLike('nome', "%{$buscaIngrediente}%"),
            )
            ->orderBy('nome')
            ->paginate(30, ['*'], 'ingredientes_page')
            ->withQueryString();
        $produtos = Produto::query()
            ->when(
                $buscaProduto !== '',
                fn ($query) => $query->whereLike('nome', "%{$buscaProduto}%"),
            )
            ->orderBy('nome')
            ->paginate(30, ['*'], 'produtos_page')
            ->withQueryString();
        $ingredientesBaixoEstoque = Ingrediente::query()
            ->whereColumn('quantidade_estoque', '<=', 'estoque_minimo')
            ->orderBy('nome')
            ->get();
        $lotesComAlertas = LoteIngrediente::query()
            ->with('ingrediente')
            ->withExpiryAlerts()
            ->orderBy('data_validade')
            ->orderBy('id')
            ->limit(10)
            ->get();

        return view('stock.index', compact(
            'ingredientes',
            'produtos',
            'ingredientesBaixoEstoque',
            'buscaIngrediente',
            'buscaProduto',
            'lotesComAlertas',
        ));
    }
}
