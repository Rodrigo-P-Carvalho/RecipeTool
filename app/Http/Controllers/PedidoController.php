<?php

namespace App\Http\Controllers;

use App\Exceptions\EstoqueInsuficienteException;
use App\Http\Requests\StorePedidoRequest;
use App\Models\Pedido;
use App\Models\Produto;
use App\Services\PedidoService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PedidoController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status_pedido' => ['nullable', Rule::in(Pedido::STATUS_FILTRO)],
        ]);
        $statusPedido = $validated['status_pedido'] ?? '';

        $pedidos = Pedido::query()
            ->with('usuario')
            ->withCount('itens')
            ->when(
                $statusPedido !== '',
                fn ($query) => $query->where('status_pedido', $statusPedido),
            )
            ->orderByDesc('data_pedido')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('orders.index', [
            'pedidos' => $pedidos,
            'statusPedido' => $statusPedido,
            'statusDisponiveis' => Pedido::STATUS_FILTRO,
        ]);
    }

    public function create(): View
    {
        return view('orders.create', [
            'produtos' => Produto::query()
                ->where('quantidade_estoque', '>', 0)
                ->orderBy('nome')
                ->get(),
        ]);
    }

    public function store(StorePedidoRequest $request, PedidoService $pedidoService): RedirectResponse
    {
        try {
            $pedido = $pedidoService->criarPedido(
                $request->validated(),
                (int) $request->user()->id,
            );
        } catch (EstoqueInsuficienteException $exception) {
            return back()
                ->withErrors(['itens' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()->route('orders.show', $pedido)
            ->with('success', 'Pedido registrado e estoque atualizado com sucesso.');
    }

    public function show(Pedido $pedido): View
    {
        $pedido->load([
            'usuario',
            'itens.produto',
            'historicoStatus.usuario',
        ]);

        return view('orders.show', [
            'pedido' => $pedido,
            'statusDisponiveis' => Pedido::STATUS_TRANSICOES,
        ]);
    }

    public function cancelar(Pedido $pedido, PedidoService $pedidoService): RedirectResponse
    {
        $pedidoService->cancelarPedido($pedido);

        return redirect()->route('orders.show', $pedido)
            ->with('success', 'Pedido cancelado e estoque dos produtos restabelecido.');
    }

    public function atualizarStatus(
        Request $request,
        Pedido $pedido,
        PedidoService $pedidoService,
    ): RedirectResponse {
        $attributes = $request->validate([
            'status_pedido' => ['required', Rule::in(Pedido::STATUS_TRANSICOES)],
        ]);

        try {
            $pedidoService->atualizarStatus($pedido, $attributes['status_pedido']);
        } catch (DomainException $exception) {
            return back()
                ->withErrors(['status_pedido' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()->route('orders.show', $pedido)
            ->with('success', 'Status do pedido atualizado com sucesso.');
    }
}
