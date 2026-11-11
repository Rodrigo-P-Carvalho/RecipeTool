<?php

namespace App\Services;

use App\Exceptions\EstoqueInsuficienteException;
use App\Models\AjusteEstoque;
use App\Models\HistoricoStatusPedido;
use App\Models\Pedido;
use App\Models\Produto;
use DomainException;
use Illuminate\Support\Facades\DB;

class PedidoService
{
    /**
     * @param  array{itens: array<int, array{produto_id: int|string, qtd: int|string}>}  $dados
     */
    public function criarPedido(array $dados, int $usuarioId): Pedido
    {
        return DB::transaction(function () use ($dados, $usuarioId): Pedido {
            $itens = $dados['itens'];
            $produtoIds = collect($itens)
                ->pluck('produto_id')
                ->map(fn (int|string $produtoId): int => (int) $produtoId)
                ->unique()
                ->sort()
                ->values();
            $produtos = Produto::query()
                ->whereIn('id', $produtoIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $quantidadesPorProduto = [];

            foreach ($itens as $item) {
                $produtoId = (int) $item['produto_id'];
                $quantidadesPorProduto[$produtoId] = ($quantidadesPorProduto[$produtoId] ?? 0)
                    + (int) $item['qtd'];
            }

            foreach ($quantidadesPorProduto as $produtoId => $quantidadeSolicitada) {
                $produto = $produtos->get($produtoId);

                if ($produto === null) {
                    throw new DomainException('Um dos produtos selecionados não existe.');
                }

                if ((float) $produto->quantidade_estoque < $quantidadeSolicitada) {
                    throw new EstoqueInsuficienteException(
                        "Estoque insuficiente para o produto: {$produto->nome}",
                    );
                }
            }

            $valorTotalCentavos = 0;

            foreach ($itens as $item) {
                $produto = $produtos->get((int) $item['produto_id']);
                $valorUnitarioCentavos = (int) round((float) $produto->preco_venda * 100);
                $valorTotalCentavos += $valorUnitarioCentavos * (int) $item['qtd'];
            }

            $pedido = Pedido::query()->create([
                'usuario_id' => $usuarioId,
                'data_pedido' => now(),
                'status_pedido' => Pedido::STATUS_AGUARDANDO_PAGAMENTO,
                'valor_total' => number_format($valorTotalCentavos / 100, 2, '.', ''),
            ]);

            foreach ($itens as $item) {
                $produto = $produtos->get((int) $item['produto_id']);
                $quantidade = (int) $item['qtd'];

                $produto->decrement('quantidade_estoque', $quantidade);
                $pedido->itens()->create([
                    'produto_id' => $produto->id,
                    'qtd' => $quantidade,
                ]);
                $this->registrarMovimentacao(
                    (int) $usuarioId,
                    $produto,
                    'venda_saida',
                    $quantidade,
                    "Venda registrada no pedido {$pedido->id}.",
                );
            }

            $this->registrarStatus($pedido, Pedido::STATUS_AGUARDANDO_PAGAMENTO, $usuarioId);

            return $pedido;
        }, attempts: 3);
    }

    public function cancelarPedido(Pedido $pedido): void
    {
        DB::transaction(function () use ($pedido): void {
            $pedido = Pedido::query()->lockForUpdate()->findOrFail($pedido->id);

            if ($this->pedidoEstaCancelado($pedido)) {
                return;
            }

            $usuarioId = auth()->id() ?? $pedido->usuario_id;

            foreach ($pedido->itens()->orderBy('produto_id')->get() as $item) {
                $produto = Produto::query()
                    ->lockForUpdate()
                    ->findOrFail($item->produto_id);

                $produto->increment('quantidade_estoque', $item->qtd);
                $this->registrarMovimentacao(
                    (int) $usuarioId,
                    $produto,
                    'cancelamento_entrada',
                    (int) $item->qtd,
                    "Devolução de estoque pelo cancelamento do pedido {$pedido->id}.",
                );
            }

            $pedido->update(['status_pedido' => Pedido::STATUS_CANCELADO]);
            $this->registrarStatus($pedido, Pedido::STATUS_CANCELADO, (int) $usuarioId);
        }, attempts: 3);
    }

    public function atualizarStatus(Pedido $pedido, string $novoStatus): void
    {
        if (! in_array($novoStatus, Pedido::STATUS_TRANSICOES, true)) {
            throw new DomainException('O status informado não é permitido.');
        }

        DB::transaction(function () use ($pedido, $novoStatus): void {
            $pedido = Pedido::query()->lockForUpdate()->findOrFail($pedido->id);

            if ($this->pedidoEstaCancelado($pedido)) {
                throw new DomainException('Um pedido cancelado não pode ser reaberto.');
            }

            if ($pedido->status_pedido === $novoStatus) {
                return;
            }

            $usuarioId = (int) (auth()->id() ?? $pedido->usuario_id);
            $pedido->update(['status_pedido' => $novoStatus]);
            $this->registrarStatus($pedido, $novoStatus, $usuarioId);
        }, attempts: 3);
    }

    private function pedidoEstaCancelado(Pedido $pedido): bool
    {
        return in_array($pedido->status_pedido, [Pedido::STATUS_CANCELADO, 'cancelado'], true);
    }

    private function registrarStatus(Pedido $pedido, string $status, int $usuarioId): HistoricoStatusPedido
    {
        return $pedido->historicoStatus()->create([
            'usuario_id' => $usuarioId,
            'status_pedido' => $status,
            'created_at' => now(),
        ]);
    }

    private function registrarMovimentacao(
        int $usuarioId,
        Produto $produto,
        string $tipo,
        int $quantidade,
        string $justificativa,
    ): AjusteEstoque {
        return $produto->ajustesEstoque()->create([
            'usuario_id' => $usuarioId,
            'data_ajuste' => now(),
            'tipo_ajuste' => $tipo,
            'quantidade' => $quantidade,
            'justificativa' => $justificativa,
        ]);
    }
}
