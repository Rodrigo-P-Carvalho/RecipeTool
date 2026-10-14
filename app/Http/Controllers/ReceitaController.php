<?php

namespace App\Http\Controllers;

use App\Models\Ingrediente;
use App\Models\Produto;
use App\Models\Receita;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReceitaController extends Controller
{
    public function index(): View
    {
        $receitas = Receita::query()->with('produto')->orderBy('id')->paginate(20);

        return view('recipes', compact('receitas'));
    }

    public function create(): View
    {
        return view('recipes.create', [
            'produtos' => Produto::query()->orderBy('nome')->get(),
            'ingredientes' => Ingrediente::query()->orderBy('nome')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $attributes = $this->validatedAttributes($request);

        $receita = DB::transaction(function () use ($attributes): Receita {
            $receita = Receita::query()->create([
                'produto_id' => $attributes['produto_id'],
                'descricao' => $attributes['descricao'],
                'custo_total' => '0.00',
            ]);

            $receita->itens()->createMany($attributes['itens']);
            $receita->recalcularCusto();

            return $receita;
        });

        return redirect()->route('recipes.show', $receita)
            ->with('success', 'Receita cadastrada com sucesso.');
    }

    public function show(Receita $receita): View
    {
        $receita->load(['produto', 'itens.ingrediente']);

        return view('recipes.show', compact('receita'));
    }

    public function edit(Receita $receita): View
    {
        $receita->load('itens');

        return view('recipes.edit', [
            'receita' => $receita,
            'produtos' => Produto::query()->orderBy('nome')->get(),
            'ingredientes' => Ingrediente::query()->orderBy('nome')->get(),
        ]);
    }

    public function update(Request $request, Receita $receita): RedirectResponse
    {
        if ($receita->producoes()->exists()) {
            return back()->withErrors([
                'receita' => 'Não é possível alterar uma receita que já possui registros de produção.',
            ]);
        }

        $attributes = $this->validatedAttributes($request, $receita);

        DB::transaction(function () use ($attributes, $receita): void {
            $receita->update([
                'produto_id' => $attributes['produto_id'],
                'descricao' => $attributes['descricao'],
            ]);

            $receita->itens()->delete();
            $receita->itens()->createMany($attributes['itens']);
            $receita->recalcularCusto();
        });

        return redirect()->route('recipes.show', $receita)
            ->with('success', 'Receita atualizada com sucesso.');
    }

    public function destroy(Receita $receita): RedirectResponse
    {
        if ($receita->producoes()->exists()) {
            return back()->withErrors([
                'receita' => 'Não é possível excluir uma receita que possui registros de produção.',
            ]);
        }

        $receita->delete();

        return redirect()->route('recipes')->with('success', 'Receita excluída com sucesso.');
    }

    /**
     * @return array{produto_id: int, descricao: string, itens: array<int, array{ingrediente_id: int, quantidade_necessaria: string}>}
     */
    private function validatedAttributes(Request $request, ?Receita $receita = null): array
    {
        return $request->validate([
            'produto_id' => [
                'required',
                'integer',
                'exists:produtos,id',
                Rule::unique('receitas', 'produto_id')->ignore($receita?->id),
            ],
            'descricao' => ['required', 'string', 'max:255'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.ingrediente_id' => [
                'required',
                'integer',
                'distinct',
                'exists:ingredientes,id',
            ],
            'itens.*.quantidade_necessaria' => ['required', 'numeric', 'gt:0'],
        ]);
    }
}
