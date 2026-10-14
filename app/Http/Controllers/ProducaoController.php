<?php

namespace App\Http\Controllers;

use App\Models\AjusteEstoque;
use App\Models\ItemEstoque;
use App\Models\Producao;
use App\Models\Receita;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProducaoController extends Controller
{
    public function index(): View
    {
        $producoes = Producao::query()
            ->with(['usuario', 'receita.produto'])
            ->latest('data_producao')
            ->paginate(20);

        return view('productions.index', compact('producoes'));
    }

    public function create(): View
    {
        return view('productions.create', [
            'receitas' => Receita::query()->with('produto')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'receita_id' => ['required', 'integer', 'exists:receitas,id'],
            'quantidade_lotes' => ['required', 'integer', 'min:1'],
            'data_producao' => ['nullable', 'date'],
        ]);

        $producao = DB::transaction(function () use ($attributes): Producao {
            $receita = Receita::query()
                ->with('itens')
                ->lockForUpdate()
                ->findOrFail($attributes['receita_id']);

            if ($receita->itens->isEmpty()) {
                throw ValidationException::withMessages([
                    'receita_id' => 'Não é possível produzir uma receita sem ingredientes.',
                ]);
            }

            foreach ($receita->itens as $item) {
                $ingrediente = $item->ingrediente()
                    ->lockForUpdate()
                    ->firstOrFail();

                $quantidadeNecessaria = round(
                    (float) $item->quantidade_necessaria * $attributes['quantidade_lotes'],
                    3,
                );

                if ((float) $ingrediente->quantidade_estoque < $quantidadeNecessaria) {
                    throw ValidationException::withMessages([
                        'receita_id' => "Estoque insuficiente para o ingrediente {$ingrediente->nome}.",
                    ]);
                }

                $ingrediente->decrement('quantidade_estoque', $quantidadeNecessaria);

                $this->registrarMovimentacao(
                    $ingrediente,
                    'producao_saida',
                    $quantidadeNecessaria,
                    "Consumo na produção da receita {$receita->id}.",
                );
            }

            $produto = $receita->produto()->lockForUpdate()->firstOrFail();
            $produto->increment('quantidade_estoque', $attributes['quantidade_lotes']);
            $this->registrarMovimentacao(
                $produto,
                'producao_entrada',
                $attributes['quantidade_lotes'],
                "Produção da receita {$receita->id}.",
            );

            return Producao::query()->create([
                'usuario_id' => Auth::id(),
                'receita_id' => $receita->id,
                'quantidade_lotes' => $attributes['quantidade_lotes'],
                'data_producao' => $attributes['data_producao'] ?? now(),
            ]);
        }, attempts: 3);

        return redirect()->route('productions.show', $producao)
            ->with('success', 'Produção registrada e estoques atualizados com sucesso.');
    }

    public function show(Producao $producao): View
    {
        $producao->load(['usuario', 'receita.produto', 'receita.itens.ingrediente']);

        return view('productions.show', compact('producao'));
    }

    private function registrarMovimentacao(
        ItemEstoque $ajustavel,
        string $tipo,
        float|int $quantidade,
        string $justificativa,
    ): AjusteEstoque {
        return $ajustavel->ajustesEstoque()->create([
            'usuario_id' => Auth::id(),
            'data_ajuste' => now(),
            'tipo_ajuste' => $tipo,
            'quantidade' => $quantidade,
            'justificativa' => $justificativa,
        ]);
    }
}
