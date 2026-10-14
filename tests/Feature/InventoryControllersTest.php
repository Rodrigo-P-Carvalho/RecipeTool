<?php

namespace Tests\Feature;

use App\Models\AjusteEstoque;
use App\Models\Ingrediente;
use App\Models\ItemReceita;
use App\Models\Pedido;
use App\Models\Producao;
use App\Models\Produto;
use App\Models\Receita;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryControllersTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_protected_inventory_routes(): void
    {
        $this->get(route('stock.index'))
            ->assertRedirect(route('login'));
    }

    public function test_ingredient_creation_records_the_initial_stock_entry(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('ingredients.store'), [
            'nome' => 'Cacau',
            'quantidade_estoque' => '3.500',
            'unidade_medida' => 'kg',
            'estoque_minimo' => '0.500',
            'status_ingrediente' => 'ativo',
            'preco_embalagem' => '25.00',
            'tamanho_embalagem' => '5000',
        ]);

        $ingrediente = Ingrediente::query()->sole();
        $response->assertRedirect(route('ingredients.show', $ingrediente))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('ajustes_estoque', [
            'usuario_id' => $user->id,
            'ajustavel_type' => Ingrediente::class,
            'ajustavel_id' => $ingrediente->id,
            'tipo_ajuste' => 'cadastro_entrada',
            'quantidade' => '3.500',
        ]);
        $this->assertDatabaseHas('ingredientes', [
            'id' => $ingrediente->id,
            'preco_embalagem' => '25.00',
            'tamanho_embalagem' => '5000.000',
            'custo_unitario' => '0.0050',
        ]);
    }

    public function test_recipe_cost_is_calculated_from_ingredient_unit_cost_and_recalculated_when_package_price_changes(): void
    {
        $user = User::factory()->create();
        $ingrediente = Ingrediente::query()->create([
            'nome' => 'Farinha',
            'quantidade_estoque' => '5000.000',
            'unidade_medida' => 'g',
            'estoque_minimo' => '100.000',
            'status_ingrediente' => 'ativo',
            'preco_embalagem' => '25.00',
            'tamanho_embalagem' => '5000.000',
            'custo_unitario' => '0.0050',
        ]);
        $produto = Produto::query()->create([
            'nome' => 'Bolo',
            'quantidade_estoque' => '0.000',
            'descricao' => 'Bolo simples',
            'preco_venda' => '20.00',
        ]);

        $response = $this->actingAs($user)->post(route('recipes.store'), [
            'produto_id' => $produto->id,
            'descricao' => 'Bolo de farinha',
            'itens' => [
                ['ingrediente_id' => $ingrediente->id, 'quantidade_necessaria' => '250.000'],
            ],
        ]);

        $receita = Receita::query()->sole();
        $response->assertRedirect(route('recipes.show', $receita))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('receitas', [
            'id' => $receita->id,
            'custo_total' => '1.25',
        ]);

        $this->put(route('ingredients.update', $ingrediente), [
            'nome' => 'Farinha',
            'unidade_medida' => 'g',
            'estoque_minimo' => '100.000',
            'status_ingrediente' => 'ativo',
            'preco_embalagem' => '50.00',
            'tamanho_embalagem' => '5000.000',
        ])->assertRedirect(route('ingredients.show', $ingrediente))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('ingredientes', [
            'id' => $ingrediente->id,
            'custo_unitario' => '0.0100',
        ]);
        $this->assertDatabaseHas('receitas', [
            'id' => $receita->id,
            'custo_total' => '2.50',
        ]);
    }

    public function test_production_deducts_ingredients_adds_products_and_records_both_movements(): void
    {
        $user = User::factory()->create();
        $ingrediente = Ingrediente::query()->create([
            'nome' => 'Farinha',
            'quantidade_estoque' => '2.000',
            'unidade_medida' => 'kg',
            'estoque_minimo' => '0.500',
            'status_ingrediente' => 'ativo',
            'preco_embalagem' => '25.00',
            'tamanho_embalagem' => '5000',
            'custo_unitario' => '0.0050',
        ]);
        $produto = Produto::query()->create([
            'nome' => 'Pão',
            'quantidade_estoque' => '3.000',
            'descricao' => 'Pão artesanal',
            'preco_venda' => '10.00',
        ]);
        $receita = Receita::query()->create([
            'produto_id' => $produto->id,
            'descricao' => 'Receita de pão',
            'custo_total' => '4.00',
        ]);
        ItemReceita::query()->create([
            'receita_id' => $receita->id,
            'ingrediente_id' => $ingrediente->id,
            'quantidade_necessaria' => '0.250',
        ]);

        $response = $this->actingAs($user)->post(route('productions.store'), [
            'receita_id' => $receita->id,
            'quantidade_lotes' => 2,
        ]);

        $producao = Producao::query()->sole();
        $response->assertRedirect(route('productions.show', $producao))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('ingredientes', [
            'id' => $ingrediente->id,
            'quantidade_estoque' => '1.500',
        ]);
        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'quantidade_estoque' => '5.000',
        ]);
        $this->assertDatabaseHas('ajustes_estoque', [
            'usuario_id' => $user->id,
            'ajustavel_type' => Ingrediente::class,
            'ajustavel_id' => $ingrediente->id,
            'tipo_ajuste' => 'producao_saida',
            'quantidade' => '0.500',
        ]);
        $this->assertDatabaseHas('ajustes_estoque', [
            'usuario_id' => $user->id,
            'ajustavel_type' => Produto::class,
            'ajustavel_id' => $produto->id,
            'tipo_ajuste' => 'producao_entrada',
            'quantidade' => '2.000',
        ]);
    }

    public function test_production_rolls_back_all_stock_changes_when_an_ingredient_is_insufficient(): void
    {
        $user = User::factory()->create();
        $ingrediente = Ingrediente::query()->create([
            'nome' => 'Farinha',
            'quantidade_estoque' => '0.300',
            'unidade_medida' => 'kg',
            'estoque_minimo' => '0.100',
            'status_ingrediente' => 'ativo',
            'preco_embalagem' => '25.00',
            'tamanho_embalagem' => '5000',
            'custo_unitario' => '0.0050',
        ]);
        $produto = Produto::query()->create([
            'nome' => 'Pão',
            'quantidade_estoque' => '3.000',
            'descricao' => 'Pão artesanal',
            'preco_venda' => '10.00',
        ]);
        $receita = Receita::query()->create([
            'produto_id' => $produto->id,
            'descricao' => 'Receita de pão',
            'custo_total' => '4.00',
        ]);
        ItemReceita::query()->create([
            'receita_id' => $receita->id,
            'ingrediente_id' => $ingrediente->id,
            'quantidade_necessaria' => '0.250',
        ]);

        $response = $this->actingAs($user)
            ->from(route('productions.create'))
            ->post(route('productions.store'), [
                'receita_id' => $receita->id,
                'quantidade_lotes' => 2,
            ]);

        $response->assertRedirect(route('productions.create'))
            ->assertSessionHasErrors('receita_id');
        $this->assertDatabaseHas('ingredientes', [
            'id' => $ingrediente->id,
            'quantidade_estoque' => '0.300',
        ]);
        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'quantidade_estoque' => '3.000',
        ]);
        $this->assertDatabaseCount('producoes', 0);
        $this->assertDatabaseCount('ajustes_estoque', 0);
    }

    public function test_manual_stock_adjustment_requires_a_justification_and_updates_polymorphic_stock(): void
    {
        $user = User::factory()->create();
        $ingrediente = Ingrediente::query()->create([
            'nome' => 'Açúcar',
            'quantidade_estoque' => '1.000',
            'unidade_medida' => 'kg',
            'estoque_minimo' => '0.200',
            'status_ingrediente' => 'ativo',
            'preco_embalagem' => '25.00',
            'tamanho_embalagem' => '5000',
            'custo_unitario' => '0.0050',
        ]);

        $response = $this->actingAs($user)
            ->from(route('stock-adjustments.create'))
            ->post(route('stock-adjustments.store'), [
                'ajustavel_type' => Ingrediente::class,
                'ajustavel_id' => $ingrediente->id,
                'tipo_ajuste' => 'entrada',
                'quantidade' => '0.500',
            ]);

        $response->assertRedirect(route('stock-adjustments.create'))
            ->assertSessionHasErrors('justificativa');
        $this->assertDatabaseHas('ingredientes', [
            'id' => $ingrediente->id,
            'quantidade_estoque' => '1.000',
        ]);
        $this->assertDatabaseCount('ajustes_estoque', 0);

        $response = $this->actingAs($user)->post(route('stock-adjustments.store'), [
            'ajustavel_type' => Ingrediente::class,
            'ajustavel_id' => $ingrediente->id,
            'tipo_ajuste' => 'entrada',
            'quantidade' => '0.500',
            'justificativa' => 'Recebimento de compra',
        ]);

        $ajuste = AjusteEstoque::query()->sole();
        $response->assertRedirect(route('stock-adjustments.show', $ajuste))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('ingredientes', [
            'id' => $ingrediente->id,
            'quantidade_estoque' => '1.500',
        ]);
        $this->assertDatabaseHas('ajustes_estoque', [
            'id' => $ajuste->id,
            'usuario_id' => $user->id,
            'ajustavel_type' => Ingrediente::class,
            'ajustavel_id' => $ingrediente->id,
            'justificativa' => 'Recebimento de compra',
        ]);
    }

    public function test_confirmed_order_deducts_stock_and_cancellation_restores_it_once(): void
    {
        $user = User::factory()->create();
        $produto = Produto::query()->create([
            'nome' => 'Brigadeiro',
            'quantidade_estoque' => '5.000',
            'descricao' => 'Brigadeiro tradicional',
            'preco_venda' => '12.50',
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
            'valor_total' => '25.00',
            'status_pedido' => 'confirmado',
        ]);
        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'quantidade_estoque' => '3.000',
        ]);
        $this->assertDatabaseHas('ajustes_estoque', [
            'ajustavel_type' => Produto::class,
            'ajustavel_id' => $produto->id,
            'tipo_ajuste' => 'venda_saida',
            'quantidade' => '2.000',
        ]);

        $this->put(route('orders.update', $pedido), ['status_pedido' => 'cancelado'])
            ->assertRedirect(route('orders.show', $pedido))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'quantidade_estoque' => '5.000',
        ]);

        $this->put(route('orders.update', $pedido), ['status_pedido' => 'cancelado'])
            ->assertRedirect(route('orders.show', $pedido));
        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'quantidade_estoque' => '5.000',
        ]);
        $this->assertDatabaseCount('ajustes_estoque', 2);
    }

    public function test_production_report_exports_ranked_quantities_and_neutralizes_spreadsheet_formulas(): void
    {
        $user = User::factory()->create();
        $produto = Produto::query()->create([
            'nome' => '=HYPERLINK("https://example.invalid")',
            'quantidade_estoque' => '0.000',
            'descricao' => 'Produto de teste',
            'preco_venda' => '10.00',
        ]);
        $receita = Receita::query()->create([
            'produto_id' => $produto->id,
            'descricao' => 'Receita de teste',
            'custo_total' => '1.00',
        ]);
        Producao::query()->create([
            'usuario_id' => $user->id,
            'receita_id' => $receita->id,
            'quantidade_lotes' => 3,
            'data_producao' => '2026-10-01 12:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reports.production'));

        $response->assertOk()
            ->assertStreamed()
            ->assertStreamedContent(
                "Produto;\"Quantidade produzida\"\n\"'=HYPERLINK(\"\"https://example.invalid\"\")\";3\n",
            );
    }
}
