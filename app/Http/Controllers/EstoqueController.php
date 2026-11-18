<?php

namespace App\Http\Controllers;

use App\Models\Ingrediente;
use App\Models\LoteIngrediente;
use App\Models\Produto;
use Illuminate\Http\RedirectResponse;
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
        $filtros = $request->session()->get('filters.stock', []);
        $buscaIngrediente = trim(
            $validated['busca_ingrediente'] ?? $filtros['busca_ingrediente'] ?? '',
        );
        $buscaProduto = trim(
            $validated['busca_produto'] ?? $filtros['busca_produto'] ?? '',
        );

        $ingredientes = Ingrediente::query()
            ->withSum([
                'lotes as quantidade_fora_validade' => fn ($query) => $query
                    ->where('quantidade_atual', '>', 0)
                    ->whereDate('data_validade', '<', today()->toDateString()),
            ], 'quantidade_atual')
            ->withSum([
                'lotes as quantidade_vencendo' => fn ($query) => $query
                    ->where('quantidade_atual', '>', 0)
                    ->whereDate('data_validade', '>=', today()->toDateString())
                    ->whereDate('data_validade', '<=', today()->addDays(7)->toDateString()),
            ], 'quantidade_atual')
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
            ->withSum([
                'lotes as quantidade_fora_validade' => fn ($query) => $query
                    ->where('quantidade_atual', '>', 0)
                    ->whereDate('data_validade', '<', today()->toDateString()),
            ], 'quantidade_atual')
            ->whereRaw(
                'quantidade_estoque - COALESCE((
                    SELECT SUM(lotes_ingredientes.quantidade_atual)
                    FROM lotes_ingredientes
                    WHERE lotes_ingredientes.ingrediente_id = ingredientes.id
                        AND lotes_ingredientes.quantidade_atual > 0
                        AND lotes_ingredientes.data_validade < ?
                ), 0) <= estoque_minimo',
                [today()->toDateString()],
            )
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

    public function search(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'busca_ingrediente' => ['nullable', 'string', 'max:100'],
            'busca_produto' => ['nullable', 'string', 'max:100'],
            'clear' => ['nullable', 'in:busca_ingrediente,busca_produto'],
        ]);

        if (($validated['clear'] ?? null) === 'busca_ingrediente') {
            $validated['busca_ingrediente'] = '';
        }

        if (($validated['clear'] ?? null) === 'busca_produto') {
            $validated['busca_produto'] = '';
        }

        $request->session()->put('filters.stock', [
            'busca_ingrediente' => trim($validated['busca_ingrediente'] ?? ''),
            'busca_produto' => trim($validated['busca_produto'] ?? ''),
        ]);

        return redirect()->route('stock.index');
    }
}
