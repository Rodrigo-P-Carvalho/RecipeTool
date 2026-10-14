<?php

namespace App\Http\Controllers;

use App\Models\Ingrediente;
use App\Models\Produto;
use Illuminate\View\View;

class EstoqueController extends Controller
{
    public function index(): View
    {
        $ingredientes = Ingrediente::query()
            ->orderBy('nome')
            ->paginate(30, ['*'], 'ingredientes_page');
        $produtos = Produto::query()
            ->orderBy('nome')
            ->paginate(30, ['*'], 'produtos_page');
        $ingredientesBaixoEstoque = Ingrediente::query()
            ->whereColumn('quantidade_estoque', '<=', 'estoque_minimo')
            ->orderBy('nome')
            ->get();

        return view('stock.index', compact(
            'ingredientes',
            'produtos',
            'ingredientesBaixoEstoque',
        ));
    }
}
