<?php

namespace App\Http\Controllers;

use App\Models\Ingrediente;
use App\Models\Receita;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class IngredienteController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $search = trim($validated['search'] ?? '');

        $ingredientes = Ingrediente::query()
            ->when(
                $search !== '',
                fn ($query) => $query->whereLike('nome', "%{$search}%"),
            )
            ->orderBy('nome')
            ->paginate(20)
            ->withQueryString();

        return view('ingredients', compact('ingredientes', 'search'));
    }

    public function create(): View
    {
        return view('ingredients.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'quantidade_estoque' => ['required', 'numeric', 'min:0'],
            'unidade_medida' => ['required', 'string', 'max:50'],
            'estoque_minimo' => ['required', 'numeric', 'min:0'],
            'status_ingrediente' => ['required', 'string', 'max:100'],
            'preco_embalagem' => ['required', 'numeric', 'min:0'],
            'tamanho_embalagem' => ['required', 'numeric', 'gt:0'],
        ]);
        $attributes['custo_unitario'] = $this->calcularCustoUnitario(
            $attributes['preco_embalagem'],
            $attributes['tamanho_embalagem'],
        );

        $ingrediente = DB::transaction(function () use ($attributes): Ingrediente {
            $ingrediente = Ingrediente::query()->create($attributes);

            if ((float) $ingrediente->quantidade_estoque > 0) {
                $ingrediente->ajustesEstoque()->create([
                    'usuario_id' => Auth::id(),
                    'data_ajuste' => now(),
                    'tipo_ajuste' => 'cadastro_entrada',
                    'quantidade' => $ingrediente->quantidade_estoque,
                    'justificativa' => 'Saldo inicial informado no cadastro do ingrediente.',
                ]);
            }

            return $ingrediente;
        });

        return redirect()->route('ingredients.show', $ingrediente)
            ->with('success', 'Ingrediente cadastrado com sucesso.');
    }

    public function show(Ingrediente $ingrediente): View
    {
        return view('ingredients.show', compact('ingrediente'));
    }

    public function edit(Ingrediente $ingrediente): View
    {
        return view('ingredients.edit', compact('ingrediente'));
    }

    public function update(Request $request, Ingrediente $ingrediente): RedirectResponse
    {
        $attributes = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'unidade_medida' => ['required', 'string', 'max:50'],
            'estoque_minimo' => ['required', 'numeric', 'min:0'],
            'status_ingrediente' => ['required', 'string', 'max:100'],
            'preco_embalagem' => ['required', 'numeric', 'min:0'],
            'tamanho_embalagem' => ['required', 'numeric', 'gt:0'],
        ]);
        $attributes['custo_unitario'] = $this->calcularCustoUnitario(
            $attributes['preco_embalagem'],
            $attributes['tamanho_embalagem'],
        );

        DB::transaction(function () use ($attributes, $ingrediente): void {
            $ingrediente->update($attributes);

            $receitas = Receita::query()
                ->whereIn(
                    'id',
                    $ingrediente->itensReceita()->select('receita_id')->distinct(),
                )
                ->get();

            foreach ($receitas as $receita) {
                $receita->recalcularCusto();
            }
        });

        return redirect()->route('ingredients.show', $ingrediente)
            ->with('success', 'Ingrediente atualizado com sucesso.');
    }

    public function destroy(Ingrediente $ingrediente): RedirectResponse
    {
        if ($ingrediente->itensReceita()->exists() || $ingrediente->ajustesEstoque()->exists()) {
            return back()->withErrors([
                'ingrediente' => 'Não é possível excluir um ingrediente usado em uma receita ou no histórico de estoque.',
            ]);
        }

        $ingrediente->delete();

        return redirect()->route('ingredients')->with('success', 'Ingrediente excluído com sucesso.');
    }

    private function calcularCustoUnitario(string|int|float $precoEmbalagem, string|int|float $tamanhoEmbalagem): string
    {
        return (string) DB::query()
            ->selectRaw(
                'ROUND((CAST(? AS NUMERIC) * 1.0) / CAST(? AS NUMERIC), 4) AS custo_unitario',
                [$precoEmbalagem, $tamanhoEmbalagem],
            )
            ->value('custo_unitario');
    }
}
