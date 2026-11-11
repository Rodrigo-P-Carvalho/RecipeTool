<?php

namespace Tests\Feature;

use App\Models\Compra;
use App\Models\Ingrediente;
use App\Models\ItemReceita;
use App\Models\LoteIngrediente;
use App\Models\Pedido;
use App\Models\Producao;
use App\Models\Produto;
use App\Models\Receita;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class BetaSuiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_tb01_creates_an_order_with_the_calculated_total_and_initial_status(): void
    {
        $user = User::factory()->create();
        $produto = $this->createProduct(stock: 5, price: '12.50');

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
            'status_pedido' => Pedido::STATUS_AGUARDANDO_PAGAMENTO,
            'valor_total' => '25.00',
        ]);
        $this->assertDatabaseHas('itens_pedido', [
            'pedido_id' => $pedido->id,
            'produto_id' => $produto->id,
            'qtd' => 2,
        ]);
    }

    public function test_tb02_decrements_finished_product_stock_after_ordering(): void
    {
        $produto = $this->createProduct(stock: 10);

        $this->actingAs(User::factory()->create())
            ->post(route('orders.store'), [
                'itens' => [
                    ['produto_id' => $produto->id, 'qtd' => 4],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'quantidade_estoque' => '6.000',
        ]);
    }

    public function test_tb03_rejects_an_order_when_product_stock_is_insufficient_without_partial_writes(): void
    {
        $user = User::factory()->create();
        $availableProduct = $this->createProduct(name: 'Bolo', stock: 5);
        $lowStockProduct = $this->createProduct(name: 'Torta', stock: 1);

        $response = $this->actingAs($user)
            ->from(route('orders.create'))
            ->post(route('orders.store'), [
                'itens' => [
                    ['produto_id' => $availableProduct->id, 'qtd' => 2],
                    ['produto_id' => $lowStockProduct->id, 'qtd' => 2],
                ],
            ]);

        $response->assertRedirect(route('orders.create'))
            ->assertSessionHasErrors([
                'itens' => 'Estoque insuficiente para o produto: Torta',
            ]);
        $this->assertDatabaseCount('pedidos', 0);
        $this->assertDatabaseCount('itens_pedido', 0);
        $this->assertDatabaseHas('produtos', [
            'id' => $availableProduct->id,
            'quantidade_estoque' => '5.000',
        ]);
        $this->assertDatabaseHas('produtos', [
            'id' => $lowStockProduct->id,
            'quantidade_estoque' => '1.000',
        ]);
    }

    public function test_tb04_records_status_changes_and_shows_the_new_status_in_the_order_list(): void
    {
        $user = User::factory()->create();
        $produto = $this->createProduct(stock: 2);
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

        $this->assertDatabaseHas('pedidos', [
            'id' => $pedido->id,
            'status_pedido' => Pedido::STATUS_PAGO,
        ]);
        $this->assertDatabaseHas('historico_status_pedidos', [
            'pedido_id' => $pedido->id,
            'status_pedido' => Pedido::STATUS_PAGO,
            'usuario_id' => $user->id,
        ]);
        $this->get(route('orders.index', ['status_pedido' => Pedido::STATUS_PAGO]))
            ->assertOk()
            ->assertSee('#'.$pedido->id)
            ->assertSee(Pedido::STATUS_PAGO);
    }

    public function test_tb05_cancels_an_order_and_restores_its_product_stock(): void
    {
        $user = User::factory()->create();
        $produto = $this->createProduct(stock: 10);
        $this->actingAs($user)->post(route('orders.store'), [
            'itens' => [
                ['produto_id' => $produto->id, 'qtd' => 5],
            ],
        ]);
        $pedido = Pedido::query()->sole();

        $this->post(route('orders.cancel', $pedido))
            ->assertRedirect(route('orders.show', $pedido))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pedidos', [
            'id' => $pedido->id,
            'status_pedido' => Pedido::STATUS_CANCELADO,
        ]);
        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'quantidade_estoque' => '10.000',
        ]);
        $this->assertDatabaseHas('historico_status_pedidos', [
            'pedido_id' => $pedido->id,
            'status_pedido' => Pedido::STATUS_CANCELADO,
        ]);
    }

    public function test_tb06_remember_me_authenticates_the_user_again_with_the_recaller_cookie(): void
    {
        $user = User::factory()->create([
            'password' => 'secret-password',
            'remember_token' => null,
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'secret-password',
            'remember' => '1',
        ]);

        $recallerName = Auth::guard()->getRecallerName();
        $response->assertRedirect(route('dashboard'))
            ->assertCookie($recallerName);
        expect($user->fresh()->remember_token)->not->toBeNull();

        $recallerValue = $response->getCookie($recallerName)->getValue();
        $this->flushSession();
        Auth::forgetGuards();
        $this->withCookie($recallerName, $recallerValue)
            ->get(route('dashboard'))
            ->assertOk();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Auth::viaRemember());
    }

    public function test_tb07_first_password_change_shows_live_requirement_targets(): void
    {
        $user = User::factory()->create([
            'precisa_trocar_senha' => true,
        ]);

        $this->actingAs($user)
            ->get(route('password.change'))
            ->assertOk()
            ->assertSee('data-password-change-form', false)
            ->assertSee('data-password-requirement="length"', false)
            ->assertSee('data-password-requirement="letters"', false)
            ->assertSee('data-password-requirement="numbers"', false)
            ->assertSee('data-password-requirement="confirmation"', false)
            ->assertSee('data-password-requirement="match"', false)
            ->assertSee('Pelo menos 8 caracteres')
            ->assertSee('letra')
            ->assertSee('número')
            ->assertSee('As senhas devem corresponder');
    }

    public function test_tb08_ingredient_stock_is_displayed_with_at_most_two_decimal_places(): void
    {
        $user = User::factory()->create();
        $ingrediente = $this->createIngredient(stock: '10.126');

        $this->actingAs($user)
            ->get(route('ingredients'))
            ->assertOk()
            ->assertSee('10,13 g');
    }

    public function test_ingredient_stock_display_automatically_uses_the_most_readable_unit_without_changing_saved_data(): void
    {
        $user = User::factory()->create();
        $gramIngredient = $this->createIngredient(name: 'Farinha em gramas', stock: '1000.000');
        $kiloIngredient = $this->createIngredient(name: 'Farinha em quilos', stock: '500.000');
        $kiloIngredient->update(['unidade_medida' => 'kg']);

        $this->actingAs($user)
            ->get(route('ingredients'))
            ->assertOk()
            ->assertSee('Farinha em gramas')
            ->assertSee('1 kg')
            ->assertSee('Farinha em quilos')
            ->assertSee('500 g');

        $this->assertDatabaseHas('ingredientes', [
            'id' => $gramIngredient->id,
            'quantidade_estoque' => '1000.000',
            'unidade_medida' => 'g',
        ]);
        $this->assertDatabaseHas('ingredientes', [
            'id' => $kiloIngredient->id,
            'quantidade_estoque' => '500.000',
            'unidade_medida' => 'kg',
        ]);
    }

    public function test_tb09_ingredient_creation_calculates_cost_per_base_unit(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->post(route('ingredients.store'), [
                'nome' => 'Farinha',
                'quantidade_inicial' => '0',
                'unidade_medida' => 'g',
                'estoque_minimo' => '0',
                'unidade_embalagem' => 'kg',
                'status_ingrediente' => 'ativo',
                'preco_embalagem' => '20.00',
                'tamanho_embalagem' => '1',
            ]);

        $ingrediente = Ingrediente::query()->sole();
        $response->assertRedirect(route('ingredients.show', $ingrediente))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('ingredientes', [
            'id' => $ingrediente->id,
            'custo_unitario' => '0.0200',
        ]);
    }

    public function test_tb10_recalculates_recipe_cost_when_ingredient_package_price_changes(): void
    {
        $user = User::factory()->create();
        $ingrediente = $this->createIngredient(stock: '1000.000', price: '20.00');
        $produto = $this->createProduct();
        $receita = $this->createRecipe($produto, $ingrediente, '250.000');
        $this->assertDatabaseHas('receitas', [
            'id' => $receita->id,
            'custo_total' => '5.00',
        ]);

        $this->actingAs($user)->put(route('ingredients.update', $ingrediente), [
            'nome' => $ingrediente->nome,
            'unidade_medida' => 'g',
            'estoque_minimo' => '0',
            'unidade_embalagem' => 'kg',
            'status_ingrediente' => 'ativo',
            'preco_embalagem' => '40.00',
            'tamanho_embalagem' => '1',
        ])->assertRedirect(route('ingredients.show', $ingrediente))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('receitas', [
            'id' => $receita->id,
            'custo_total' => '10.00',
        ]);
    }

    public function test_tb11_production_consumes_ingredients_and_adds_finished_products(): void
    {
        $user = User::factory()->create();
        $ingrediente = $this->createIngredient(stock: '1500.000');
        $produto = $this->createProduct(stock: 3);
        $receita = $this->createRecipe($produto, $ingrediente, '200.000');

        $response = $this->actingAs($user)->post(route('productions.store'), [
            'receita_id' => $receita->id,
            'quantidade_lotes' => 2,
        ]);

        $producao = Producao::query()->sole();
        $response->assertRedirect(route('productions.show', $producao))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('ingredientes', [
            'id' => $ingrediente->id,
            'quantidade_estoque' => '1100.000',
        ]);
        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'quantidade_estoque' => '5.000',
        ]);
    }

    public function test_tb12_rolls_back_all_stock_changes_when_production_has_insufficient_ingredients(): void
    {
        $user = User::factory()->create();
        $availableIngredient = $this->createIngredient(name: 'Farinha', stock: '1000.000');
        $emptyIngredient = $this->createIngredient(name: 'Açúcar', stock: '0.000');
        $produto = $this->createProduct(stock: 2);
        $receita = $this->createRecipe($produto, $availableIngredient, '500.000');
        $receita->itens()->create([
            'ingrediente_id' => $emptyIngredient->id,
            'quantidade_necessaria' => '500.000',
        ]);

        $response = $this->actingAs($user)
            ->from(route('productions.create'))
            ->post(route('productions.store'), [
                'receita_id' => $receita->id,
                'quantidade_lotes' => 1,
            ]);

        $response->assertRedirect(route('productions.create'))
            ->assertSessionHasErrors('receita_id');
        $this->assertDatabaseCount('producoes', 0);
        $this->assertDatabaseCount('ajustes_estoque', 0);
        $this->assertDatabaseHas('ingredientes', [
            'id' => $availableIngredient->id,
            'quantidade_estoque' => '1000.000',
        ]);
        $this->assertDatabaseHas('ingredientes', [
            'id' => $emptyIngredient->id,
            'quantidade_estoque' => '0.000',
        ]);
        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'quantidade_estoque' => '2.000',
        ]);
    }

    public function test_tb13_prevents_deleting_an_ingredient_used_by_a_recipe(): void
    {
        $user = User::factory()->create();
        $ingrediente = $this->createIngredient(stock: '1000.000');
        $produto = $this->createProduct();
        $this->createRecipe($produto, $ingrediente, '100.000');

        $response = $this->actingAs($user)
            ->from(route('ingredients'))
            ->delete(route('ingredients.destroy', $ingrediente));

        $response->assertRedirect(route('ingredients'))
            ->assertSessionHasErrors('ingrediente');
        $this->assertStringContainsString(
            'inativar',
            $response->getSession()->get('errors')->first('ingrediente'),
        );

        $this->assertModelExists($ingrediente);
    }

    public function test_tb14_manual_stock_adjustment_changes_balance_and_stores_user_and_justification(): void
    {
        $user = User::factory()->create();
        $produto = $this->createProduct(stock: 5);

        $response = $this->actingAs($user)->post(route('stock-adjustments.store'), [
            'ajustavel_type' => Produto::class,
            'ajustavel_id' => $produto->id,
            'tipo_ajuste' => 'entrada',
            'quantidade' => 3,
            'justificativa' => 'Recebimento de compra',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'quantidade_estoque' => '8.000',
        ]);
        $this->assertDatabaseHas('ajustes_estoque', [
            'usuario_id' => $user->id,
            'ajustavel_type' => Produto::class,
            'ajustavel_id' => $produto->id,
            'tipo_ajuste' => 'entrada',
            'quantidade' => '3.000',
            'justificativa' => 'Recebimento de compra',
        ]);
    }

    public function test_tb15_purchase_adds_stock_and_expired_lots_remain_visible_and_stored(): void
    {
        $user = User::factory()->create();
        $ingrediente = $this->createIngredient(stock: '1000.000');
        $ingrediente->update(['unidade_medida' => 'kg']);

        $response = $this->actingAs($user)->post(route('purchases.store'), [
            'data_compra' => now()->toDateString(),
            'observacoes' => 'Compra de teste beta',
            'itens' => [
                [
                    'ingrediente_id' => $ingrediente->id,
                    'quantidade' => '1.5',
                    'preco_custo' => '40.00',
                    'data_validade' => '2020-01-01',
                    'codigo_lote' => 'LOTE-VENCIDO',
                ],
            ],
        ]);

        $compra = Compra::query()->sole();
        $lote = LoteIngrediente::query()->sole();
        $response->assertRedirect(route('purchases.index'))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('compras', [
            'id' => $compra->id,
            'usuario_id' => $user->id,
            'valor_total' => '40.00',
        ]);
        $this->assertDatabaseHas('ingredientes', [
            'id' => $ingrediente->id,
            'quantidade_estoque' => '2500.000',
        ]);
        $this->assertDatabaseHas('lotes_ingredientes', [
            'id' => $lote->id,
            'compra_id' => $compra->id,
            'ingrediente_id' => $ingrediente->id,
            'quantidade_inicial' => '1500.000',
            'quantidade_atual' => '1500.000',
        ]);
        $this->assertSame('2020-01-01', $lote->data_validade->toDateString());

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Alertas de lotes vencidos')
            ->assertSee('LOTE-VENCIDO')
            ->assertSee('Vencido')
            ->assertSee('Registrar ajuste manual');
        $this->get(route('stock.index'))
            ->assertOk()
            ->assertSee('Lotes vencidos ou próximos do vencimento')
            ->assertSee('LOTE-VENCIDO');

        $this->assertModelExists($lote);
        $this->assertDatabaseHas('lotes_ingredientes', [
            'id' => $lote->id,
            'quantidade_atual' => '1500.000',
        ]);
    }

    public function test_production_consumes_ingredient_lots_by_nearest_expiration_date(): void
    {
        $user = User::factory()->create();
        $ingrediente = $this->createIngredient(stock: '0.000');
        $produto = $this->createProduct();
        $this->createRecipe($produto, $ingrediente, '1200.000');

        $this->actingAs($user)->post(route('purchases.store'), [
            'data_compra' => now()->toDateString(),
            'itens' => [
                [
                    'ingrediente_id' => $ingrediente->id,
                    'quantidade' => '1000',
                    'preco_custo' => '10.00',
                    'data_validade' => now()->addDays(2)->toDateString(),
                    'codigo_lote' => 'LOTE-PRIMEIRO',
                ],
                [
                    'ingrediente_id' => $ingrediente->id,
                    'quantidade' => '500',
                    'preco_custo' => '8.00',
                    'data_validade' => now()->addDays(30)->toDateString(),
                    'codigo_lote' => 'LOTE-SEGUNDO',
                ],
            ],
        ])->assertRedirect(route('purchases.index'));

        $receita = Receita::query()->sole();
        $this->post(route('productions.store'), [
            'receita_id' => $receita->id,
            'quantidade_lotes' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('lotes_ingredientes', [
            'codigo_lote' => 'LOTE-PRIMEIRO',
            'quantidade_atual' => '0.000',
        ]);
        $this->assertDatabaseHas('lotes_ingredientes', [
            'codigo_lote' => 'LOTE-SEGUNDO',
            'quantidade_atual' => '300.000',
        ]);
        $this->assertDatabaseHas('ingredientes', [
            'id' => $ingrediente->id,
            'quantidade_estoque' => '300.000',
        ]);
    }

    public function test_purchase_rejects_fractional_quantities_for_unit_measure_ingredients(): void
    {
        $user = User::factory()->create();
        $ingrediente = $this->createIngredient(stock: '2.000');
        $ingrediente->update([
            'unidade_medida' => 'un',
            'unidade_embalagem' => 'un',
        ]);

        $this->actingAs($user)
            ->from(route('purchases.create'))
            ->post(route('purchases.store'), [
                'data_compra' => now()->toDateString(),
                'itens' => [
                    [
                        'ingrediente_id' => $ingrediente->id,
                        'quantidade' => '1.5',
                        'preco_custo' => '5.00',
                        'data_validade' => now()->addDays(30)->toDateString(),
                    ],
                ],
            ])
            ->assertSessionHasErrors('itens.0.quantidade')
            ->assertRedirect(route('purchases.create'));

        $this->assertDatabaseCount('compras', 0);
        $this->assertDatabaseHas('ingredientes', [
            'id' => $ingrediente->id,
            'quantidade_estoque' => '2.000',
        ]);
    }

    public function test_tb16_production_can_be_started_from_the_dashboard_and_submitted_directly(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('productions.create').'"', false);

        $this->get(route('productions.create'))
            ->assertOk()
            ->assertSee('action="'.route('productions.store').'"', false)
            ->assertSee('Confirmar produção');
    }

    public function test_tb17_authenticated_layout_contains_mobile_navigation_and_viewport_metadata(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('name="viewport" content="width=device-width, initial-scale=1"', false)
            ->assertSee('id="menu-lateral-botao"', false)
            ->assertSee('aria-label="Abrir menu"', false);
    }

    private function createProduct(
        string $name = 'Produto de teste',
        int $stock = 0,
        string $price = '10.00',
    ): Produto {
        return Produto::query()->create([
            'nome' => $name,
            'quantidade_estoque' => number_format($stock, 3, '.', ''),
            'descricao' => 'Produto preparado para testes beta.',
            'preco_venda' => $price,
        ]);
    }

    private function createIngredient(
        string $name = 'Ingrediente de teste',
        string $stock = '1000.000',
        string $price = '20.00',
    ): Ingrediente {
        return Ingrediente::query()->create([
            'nome' => $name,
            'quantidade_estoque' => $stock,
            'unidade_medida' => 'g',
            'estoque_minimo' => '100.000',
            'unidade_embalagem' => 'kg',
            'status_ingrediente' => 'ativo',
            'preco_embalagem' => $price,
            'tamanho_embalagem' => '1.000',
        ]);
    }

    private function createRecipe(
        Produto $produto,
        Ingrediente $ingrediente,
        string $quantidade,
    ): Receita {
        $receita = Receita::query()->create([
            'produto_id' => $produto->id,
            'descricao' => 'Receita preparada para teste beta.',
            'custo_total' => '0.00',
        ]);
        ItemReceita::query()->create([
            'receita_id' => $receita->id,
            'ingrediente_id' => $ingrediente->id,
            'quantidade_necessaria' => $quantidade,
        ]);
        $receita->recalcularCusto();

        return $receita->refresh();
    }
}
