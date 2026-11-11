<?php

namespace App\Http\Controllers;

use App\Models\AjusteEstoque;
use App\Models\Ingrediente;
use App\Models\ItemEstoque;
use App\Models\Produto;
use App\Services\LoteIngredienteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AjusteEstoqueController extends Controller
{
    public function index(): View
    {
        $ajustes = AjusteEstoque::query()
            ->with(['usuario', 'ajustavel'])
            ->latest('data_ajuste')
            ->paginate(30);

        return view('stock-adjustments.index', compact('ajustes'));
    }

    public function create(): View
    {
        return view('stock-adjustments.create', [
            'ingredientes' => Ingrediente::query()->orderBy('nome')->get(),
            'produtos' => Produto::query()->orderBy('nome')->get(),
        ]);
    }

    public function store(Request $request, LoteIngredienteService $loteIngredienteService): RedirectResponse
    {
        $attributes = $request->validate([
            'ajustavel_type' => [
                'required',
                Rule::in([Ingrediente::class, Produto::class]),
            ],
            'ajustavel_id' => [
                'required',
                'integer',
                Rule::exists($request->input('ajustavel_type') === Ingrediente::class
                    ? 'ingredientes'
                    : 'produtos', 'id'),
            ],
            'tipo_ajuste' => ['required', Rule::in(['entrada', 'saida'])],
            'justificativa' => ['required', 'string', 'max:255'],
            'data_ajuste' => ['nullable', 'date'],
        ]);

        $ajuste = DB::transaction(function () use ($attributes, $request, $loteIngredienteService): AjusteEstoque {
            $modelClass = $attributes['ajustavel_type'];
            /** @var ItemEstoque $ajustavel */
            $ajustavel = $modelClass::query()
                ->lockForUpdate()
                ->findOrFail($attributes['ajustavel_id']);
            $quantidadeInteira = $ajustavel instanceof Produto
                || ($ajustavel instanceof Ingrediente && $ajustavel->unidade_medida->value === 'un');
            $quantidadeRules = $quantidadeInteira
                ? ['required', 'integer', 'min:1']
                : ['required', 'numeric', 'gt:0'];
            $quantidade = (float) $request->validate([
                'quantidade' => $quantidadeRules,
            ])['quantidade'];

            if ($ajustavel instanceof Ingrediente) {
                $quantidade = $ajustavel->unidade_medida->converterParaBase($quantidade);
            }

            $saldo = (float) $ajustavel->quantidade_estoque;

            if ($attributes['tipo_ajuste'] === 'saida' && $saldo < $quantidade) {
                throw ValidationException::withMessages([
                    'quantidade' => "O estoque de {$ajustavel->nome} é insuficiente para esse ajuste.",
                ]);
            }

            if (
                $attributes['tipo_ajuste'] === 'saida'
                && $ajustavel instanceof Ingrediente
            ) {
                $loteIngredienteService->consumir($ajustavel, $quantidade);
            }

            $ajustavel->increment(
                'quantidade_estoque',
                $attributes['tipo_ajuste'] === 'entrada' ? $quantidade : -$quantidade,
            );

            return $ajustavel->ajustesEstoque()->create([
                'usuario_id' => Auth::id(),
                'data_ajuste' => $attributes['data_ajuste'] ?? now(),
                'tipo_ajuste' => $attributes['tipo_ajuste'],
                'quantidade' => $quantidade,
                'justificativa' => $attributes['justificativa'],
            ]);
        }, attempts: 3);

        return redirect()->route('stock-adjustments.show', $ajuste)
            ->with('success', 'Ajuste de estoque registrado com sucesso.');
    }

    public function show(AjusteEstoque $ajusteEstoque): View
    {
        $ajusteEstoque->load(['usuario', 'ajustavel']);

        return view('stock-adjustments.show', ['ajuste' => $ajusteEstoque]);
    }
}
