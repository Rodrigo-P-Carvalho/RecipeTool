<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PedidoTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_creation_saves_items_total_status_history_and_decrements_product_stock(): void
    {
        $user = User::factory()->create();
        $produto = Produto::query()->create([
            'nome' => 'Bolo de cenoura',
            'quantidade_estoque' => '8.000',
            'descricao' => 'Bolo caseiro',
            'preco_venda' => '18.50',
        ]);

        $response = $this->actingAs($user)->post(route('orders.store'), [
            'itens' => [
                ['produto_id' => $produto->id, 'qtd' => 2],
            ],
        ]);

        $pedido = Pedido::query()->sole();
        $response->assertRedirect(route('orders.show', $pedido))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('pedidos', [
            'id' => $pedido->id,
            'usuario_id' => $user->id,
            'status_pedido' => Pedido::STATUS_AGUARDANDO_PAGAMENTO,
            'valor_total' => '37.00',
        ]);
        $this->assertDatabaseHas('itens_pedido', [
            'pedido_id' => $pedido->id,
            'produto_id' => $produto->id,
            'qtd' => 2,
        ]);
        expect($pedido->itens()->with('produto')->firstOrFail()->subtotal)->toBe('37.00');
        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'quantidade_estoque' => '6.000',
        ]);
        $this->assertDatabaseHas('historico_status_pedidos', [
            'pedido_id' => $pedido->id,
            'usuario_id' => $user->id,
            'status_pedido' => Pedido::STATUS_AGUARDANDO_PAGAMENTO,
        ]);
        $this->assertDatabaseHas('ajustes_estoque', [
            'ajustavel_type' => Produto::class,
            'ajustavel_id' => $produto->id,
            'tipo_ajuste' => 'venda_saida',
            'quantidade' => '2.000',
        ]);
    }

    public function test_order_creation_rolls_back_when_any_product_has_insufficient_stock(): void
    {
        $user = User::factory()->create();
        $produtoDisponivel = Produto::query()->create([
            'nome' => 'Cupcake',
            'quantidade_estoque' => '4.000',
            'descricao' => 'Cupcake de baunilha',
            'preco_venda' => '8.00',
        ]);
        $produtoSemSaldo = Produto::query()->create([
            'nome' => 'Torta de limão',
            'quantidade_estoque' => '1.000',
            'descricao' => 'Torta gelada',
            'preco_venda' => '32.00',
        ]);

        $response = $this->followingRedirects()->actingAs($user)
            ->from(route('orders.create'))
            ->post(route('orders.store'), [
                'itens' => [
                    ['produto_id' => $produtoDisponivel->id, 'qtd' => 2],
                    ['produto_id' => $produtoSemSaldo->id, 'qtd' => 2],
                ],
            ]);

        $response->assertOk()
            ->assertSee('Estoque insuficiente para o produto: Torta de limão');
        expect(substr_count(
            $response->getContent(),
            'Estoque insuficiente para o produto: Torta de limão',
        ))->toBe(1);
        $this->assertDatabaseCount('pedidos', 0);
        $this->assertDatabaseCount('itens_pedido', 0);
        $this->assertDatabaseCount('historico_status_pedidos', 0);
        $this->assertDatabaseCount('ajustes_estoque', 0);
        $this->assertDatabaseHas('produtos', [
            'id' => $produtoDisponivel->id,
            'quantidade_estoque' => '4.000',
        ]);
        $this->assertDatabaseHas('produtos', [
            'id' => $produtoSemSaldo->id,
            'quantidade_estoque' => '1.000',
        ]);
    }

    public function test_cancelling_a_order_restores_product_stock_only_once_and_records_status_history(): void
    {
        $user = User::factory()->create();
        $produto = Produto::query()->create([
            'nome' => 'Pão de mel',
            'quantidade_estoque' => '5.000',
            'descricao' => 'Pão de mel recheado',
            'preco_venda' => '9.00',
        ]);
        $this->actingAs($user)->post(route('orders.store'), [
            'itens' => [
                ['produto_id' => $produto->id, 'qtd' => 3],
            ],
        ]);
        $pedido = Pedido::query()->sole();

        $response = $this->post(route('orders.cancel', $pedido));

        $response->assertRedirect(route('orders.show', $pedido))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('pedidos', [
            'id' => $pedido->id,
            'status_pedido' => Pedido::STATUS_CANCELADO,
        ]);
        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'quantidade_estoque' => '5.000',
        ]);
        $this->assertDatabaseCount('historico_status_pedidos', 2);
        $this->assertDatabaseHas('historico_status_pedidos', [
            'pedido_id' => $pedido->id,
            'status_pedido' => Pedido::STATUS_CANCELADO,
        ]);

        $this->post(route('orders.cancel', $pedido))
            ->assertRedirect(route('orders.show', $pedido));
        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'quantidade_estoque' => '5.000',
        ]);
        $this->assertDatabaseCount('historico_status_pedidos', 2);
        $this->assertDatabaseCount('ajustes_estoque', 2);
    }

    public function test_status_changes_are_recorded_and_orders_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $produto = Produto::query()->create([
            'nome' => 'Biscoitos',
            'quantidade_estoque' => '4.000',
            'descricao' => 'Biscoitos amanteigados',
            'preco_venda' => '6.00',
        ]);
        $this->actingAs($user)->post(route('orders.store'), [
            'itens' => [
                ['produto_id' => $produto->id, 'qtd' => 1],
            ],
        ]);
        $pedido = Pedido::query()->sole();

        $this->patch(route('orders.status.update', $pedido), [
            'status_pedido' => Pedido::STATUS_PAGO,
        ])->assertRedirect(route('orders.show', $pedido))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('historico_status_pedidos', [
            'pedido_id' => $pedido->id,
            'status_pedido' => Pedido::STATUS_PAGO,
            'usuario_id' => $user->id,
        ]);
        $this->get(route('orders.index', ['status_pedido' => Pedido::STATUS_PAGO]))
            ->assertOk()
            ->assertSee('#'.$pedido->id)
            ->assertSee(Pedido::STATUS_PAGO);
        $this->get(route('orders.show', $pedido))
            ->assertOk()
            ->assertSee($user->email)
            ->assertSee(Pedido::STATUS_AGUARDANDO_PAGAMENTO)
            ->assertSee(Pedido::STATUS_PAGO);
    }

    public function test_order_form_only_offers_products_with_available_stock(): void
    {
        $user = User::factory()->create();
        Produto::query()->create([
            'nome' => 'Produto disponível',
            'quantidade_estoque' => '2.000',
            'descricao' => 'Produto pronto',
            'preco_venda' => '5.00',
        ]);
        Produto::query()->create([
            'nome' => 'Produto sem saldo',
            'quantidade_estoque' => '0.000',
            'descricao' => 'Produto esgotado',
            'preco_venda' => '7.00',
        ]);

        $this->actingAs($user)
            ->get(route('orders.create'))
            ->assertOk()
            ->assertSee('Produto disponível')
            ->assertDontSee('Produto sem saldo');
    }
}
