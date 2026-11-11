<?php

namespace App\Http\Controllers;

use App\Enums\UnidadeMedida;
use App\Models\Ingrediente;
use App\Models\Receita;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
        return view('ingredients.create', [
            'unidadesMedida' => UnidadeMedida::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $unidadeMedida = $request->input('unidade_medida');

        $attributes = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'quantidade_inicial' => $this->regrasQuantidade($unidadeMedida),
            'unidade_medida' => ['required', Rule::enum(UnidadeMedida::class)],
            'estoque_minimo' => $this->regrasQuantidade($unidadeMedida),
            'unidade_embalagem' => ['required', Rule::enum(UnidadeMedida::class)],
            'status_ingrediente' => ['required', 'string', 'max:100'],
            'preco_embalagem' => ['required', 'numeric', 'min:0'],
            'tamanho_embalagem' => $this->regrasTamanhoEmbalagem($unidadeMedida),
        ]);
        $this->validarUnidadesCompativeis($attributes);

        $unidade = UnidadeMedida::from($attributes['unidade_medida']);
        $attributes['quantidade_estoque'] = $unidade->converterParaBase(
            (float) $attributes['quantidade_inicial'],
        );
        $attributes['estoque_minimo'] = $unidade->converterParaBase(
            (float) $attributes['estoque_minimo'],
        );
        unset($attributes['quantidade_inicial']);

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
        return view('ingredients.edit', [
            'ingrediente' => $ingrediente,
            'unidadesMedida' => UnidadeMedida::cases(),
        ]);
    }

    public function update(Request $request, Ingrediente $ingrediente): RedirectResponse
    {
        $unidadeMedida = $request->input('unidade_medida');

        $attributes = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'unidade_medida' => ['required', Rule::enum(UnidadeMedida::class)],
            'estoque_minimo' => $this->regrasQuantidade($unidadeMedida),
            'unidade_embalagem' => ['required', Rule::enum(UnidadeMedida::class)],
            'status_ingrediente' => ['required', 'string', 'max:100'],
            'preco_embalagem' => ['required', 'numeric', 'min:0'],
            'tamanho_embalagem' => $this->regrasTamanhoEmbalagem($unidadeMedida),
        ]);
        $this->validarUnidadesCompativeis(
            $attributes,
            UnidadeMedida::from($ingrediente->getRawOriginal('unidade_medida')),
        );

        $attributes['estoque_minimo'] = UnidadeMedida::from($attributes['unidade_medida'])
            ->converterParaBase((float) $attributes['estoque_minimo']);

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
                'ingrediente' => 'Este ingrediente está em uso em uma receita ou no histórico de estoque e não pode ser excluído. Recomendamos inativar o item.',
            ]);
        }

        $ingrediente->delete();

        return redirect()->route('ingredients')->with('success', 'Ingrediente excluído com sucesso.');
    }

    /**
     * @param array{unidade_medida: string, unidade_embalagem: string} $attributes
     */
    private function validarUnidadesCompativeis(array $attributes, ?UnidadeMedida $unidadeOriginal = null): void
    {
        $unidadeMedida = UnidadeMedida::from($attributes['unidade_medida']);
        $unidadeEmbalagem = UnidadeMedida::from($attributes['unidade_embalagem']);

        if ($unidadeOriginal !== null && $unidadeOriginal->tipo() !== $unidadeMedida->tipo()) {
            throw ValidationException::withMessages([
                'unidade_medida' => 'Não é possível alterar o tipo de medida de um ingrediente já cadastrado.',
            ]);
        }

        if ($unidadeMedida->tipo() !== $unidadeEmbalagem->tipo()) {
            throw ValidationException::withMessages([
                'unidade_embalagem' => 'A unidade da embalagem deve ser compatível com a unidade de medida.',
            ]);
        }
    }

    /**
     * @return array<int, string>
     */
    private function regrasQuantidade(mixed $unidadeMedida): array
    {
        return $unidadeMedida === UnidadeMedida::Un->value
            ? ['required', 'integer', 'min:0']
            : ['required', 'numeric', 'min:0'];
    }

    /**
     * @return array<int, string>
     */
    private function regrasTamanhoEmbalagem(mixed $unidadeMedida): array
    {
        return $unidadeMedida === UnidadeMedida::Un->value
            ? ['required', 'integer', 'gt:0']
            : ['required', 'numeric', 'gt:0'];
    }
}
