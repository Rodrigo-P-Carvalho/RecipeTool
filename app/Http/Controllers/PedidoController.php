<?php

namespace App\Http\Controllers;

use App\Models\AjusteEstoque;
use App\Models\ItemEstoque;
use App\Models\Pedido;
use App\Models\Produto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PedidoController extends Controller
{
    public function index(): View
    {
        $pedidos = Pedido::query()
            ->with(['usuario', 'itens.produto'])
            ->latest('data_pedido')
            ->paginate(20);

        return view('orders.index', compact('pedidos'));
    }

    public function create(): View
    {
        return view('orders.create', [
            'produtos' => Produto::query()->orderBy('nome')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'data_pedido' => ['nullable', 'date'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.produto_id' => [
                'required',
                'integer',
                'distinct',
                'exists:produtos,id',
            ],
            'itens.*.qtd' => ['required', 'integer', 'min:1'],
        ]);

        $pedido = DB::transaction(function () use ($attributes): Pedido {
            $pedido = Pedido::query()->create([
                'usuario_id' => Auth::id(),
                'data_pedido' => $attributes['data_pedido'] ?? now(),
                'status_pedido' => 'confirmado',
                'valor_total' => '0.00',
            ]);

            $total = 0;

            foreach ($attributes['itens'] as $item) {
                $produto = Produto::query()
                    ->lockForUpdate()
                    ->findOrFail($item['produto_id']);

                if ((float) $produto->quantidade_estoque < $item['qtd']) {
                    throw ValidationException::withMessages([
                        'itens' => "Estoque insuficiente para o produto {$produto->nome}.",
                    ]);
                }

                $produto->decrement('quantidade_estoque', $item['qtd']);
                $pedido->itens()->create([
                    'produto_id' => $produto->id,
                    'qtd' => $item['qtd'],
                ]);

                $this->registrarMovimentacao(
                    $produto,
                    'venda_saida',
                    $item['qtd'],
                    "Venda registrada no pedido {$pedido->id}.",
                );

                $total += round((float) $produto->preco_venda * $item['qtd'] * 100);
            }

            $pedido->update(['valor_total' => number_format($total / 100, 2, '.', '')]);

            return $pedido;
        }, attempts: 3);

        return redirect()->route('orders.show', $pedido)
            ->with('success', 'Pedido registrado e estoque atualizado com sucesso.');
    }

    public function show(Pedido $pedido): View
    {
        $pedido->load(['usuario', 'itens.produto']);

        return view('orders.show', compact('pedido'));
    }

    public function edit(Pedido $pedido): View
    {
        return view('orders.edit', compact('pedido'));
    }

    public function update(Request $request, Pedido $pedido): RedirectResponse
    {
        $attributes = $request->validate([
            'status_pedido' => [
                'required',
                Rule::in(['confirmado', 'concluido', 'cancelado']),
            ],
        ]);

        DB::transaction(function () use ($attributes, $pedido): void {
            $pedido = Pedido::query()->lockForUpdate()->findOrFail($pedido->id);

            if ($pedido->status_pedido === 'cancelado' && $attributes['status_pedido'] !== 'cancelado') {
                throw ValidationException::withMessages([
                    'status_pedido' => 'Um pedido cancelado não pode ser reaberto.',
                ]);
            }

            if ($attributes['status_pedido'] === 'cancelado' && $pedido->status_pedido !== 'cancelado') {
                foreach ($pedido->itens as $item) {
                    $produto = $item->produto()->lockForUpdate()->firstOrFail();
                    $produto->increment('quantidade_estoque', $item->qtd);

                    $this->registrarMovimentacao(
                        $produto,
                        'cancelamento_entrada',
                        $item->qtd,
                        "Devolução de estoque pelo cancelamento do pedido {$pedido->id}.",
                    );
                }
            }

            $pedido->update($attributes);
        }, attempts: 3);

        return redirect()->route('orders.show', $pedido)
            ->with('success', 'Status do pedido atualizado com sucesso.');
    }

    private function registrarMovimentacao(
        ItemEstoque $ajustavel,
        string $tipo,
        int $quantidade,
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
