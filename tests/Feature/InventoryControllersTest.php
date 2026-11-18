<?php

namespace Tests\Feature;

use App\Models\AjusteEstoque;
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
            'quantidade_inicial' => '3.500',
            'unidade_medida' => 'kg',
            'estoque_minimo' => '0.500',
            'unidade_embalagem' => 'kg',
            'status_ingrediente' => 'ativo',
            'preco_embalagem' => '25.00',
            'tamanho_embalagem' => '5',
        ]);

        $ingrediente = Ingrediente::query()->sole();
        $response->assertRedirect(route('ingredients.show', $ingrediente))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('ajustes_estoque', [
            'usuario_id' => $user->id,
            'ajustavel_type' => Ingrediente::class,
            'ajustavel_id' => $ingrediente->id,
            'tipo_ajuste' => 'cadastro_entrada',
            'quantidade' => '3500.000',
        ]);
        $this->assertDatabaseHas('ingredientes', [
            'id' => $ingrediente->id,
            'quantidade_estoque' => '3500.000',
            'estoque_minimo' => '500.000',
            'preco_embalagem' => '25.00',
            'tamanho_embalagem' => '5.000',
            'custo_unitario' => '0.0050',
        ]);

        expect($ingrediente->fresh()->quantidade_estoque_formatada)->toBe('3,5 kg')
            ->and($ingrediente->fresh()->estoque_minimo_formatado)->toBe('500 g')
            ->and($ingrediente->fresh()->custo_formatado)->toBe('R$ 5,00 / kg');
    }

    public function test_unit_ingredients_reject_fractional_stock_and_packaging_quantities(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('ingredients.store'), [
            'nome' => 'Ovos',
            'quantidade_inicial' => '1.5',
            'unidade_medida' => 'un',
            'estoque_minimo' => '0.5',
            'unidade_embalagem' => 'un',
            'status_ingrediente' => 'ativo',
            'preco_embalagem' => '12.00',
            'tamanho_embalagem' => '6.5',
        ]);

        $response->assertSessionHasErrors([
            'quantidade_inicial',
            'estoque_minimo',
            'tamanho_embalagem',
        ]);
        $this->assertDatabaseCount('ingredientes', 0);

        $ingrediente = Ingrediente::query()->create([
            'nome' => 'Ovos',
            'quantidade_estoque' => '4.000',
            'unidade_medida' => 'un',
            'estoque_minimo' => '1.000',
            'unidade_embalagem' => 'un',
            'status_ingrediente' => 'ativo',
            'preco_embalagem' => '12.00',
            'tamanho_embalagem' => '6.000',
        ]);

        $this->get(route('ingredients.edit', $ingrediente))
            ->assertOk()
            ->assertSee('step="1"', false);
    }

    public function test_product_stock_is_integer_validated_and_displayed_without_trailing_decimals(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('products.create'))
            ->assertOk()
            ->assertSee('step="1"', false);

        $this->post(route('products.store'), [
            'nome' => 'Pão de mel',
            'quantidade_estoque' => '1.5',
            'descricao' => 'Pão de mel recheado',
            'preco_venda' => '8.00',
        ])->assertSessionHasErrors('quantidade_estoque');
        $this->assertDatabaseCount('produtos', 0);

        $produto = Produto::query()->create([
            'nome' => 'Pão de mel',
            'quantidade_estoque' => '6.000',
            'descricao' => 'Pão de mel recheado',
            'preco_venda' => '8.00',
        ]);

        $this->get(route('products'))
            ->assertOk()
            ->assertSee('6')
            ->assertDontSee('6,000');
        $this->get(route('products.show', $produto))
            ->assertOk()
            ->assertSee('6')
            ->assertDontSee('6,000');
        $this->get(route('stock.index'))
            ->assertOk()
            ->assertSee('6')
            ->assertDontSee('6,000');
    }

    public function test_stock_overview_shows_expiry_alerts_and_uses_usable_stock_for_replenishment(): void
    {
        $user = User::factory()->create();
        $ingrediente = Ingrediente::query()->create([
            'nome' => 'Ingrediente com alertas',
            'quantidade_estoque' => '500.000',
            'unidade_medida' => 'g',
            'estoque_minimo' => '300.000',
            'unidade_embalagem' => 'g',
            'status_ingrediente' => 'ativo',
            'preco_embalagem' => '10.00',
            'tamanho_embalagem' => '1000.000',
        ]);
        $compra = Compra::query()->create([
            'usuario_id' => $user->id,
            'data_compra' => now(),
            'valor_total' => '4.00',
        ]);
        LoteIngrediente::query()->create([
            'compra_id' => $compra->id,
            'ingrediente_id' => $ingrediente->id,
            'quantidade_inicial' => '300.000',
            'quantidade_atual' => '300.000',
            'preco_custo' => '3.00',
            'data_validade' => today()->subDay(),
        ]);
        LoteIngrediente::query()->create([
            'compra_id' => $compra->id,
            'ingrediente_id' => $ingrediente->id,
            'quantidade_inicial' => '100.000',
            'quantidade_atual' => '100.000',
            'preco_custo' => '1.00',
            'data_validade' => today()->addDays(7),
        ]);

        $this->actingAs($user)
            ->get(route('stock.index'))
            ->assertOk()
            ->assertSee('saldo utilizável 200 g')
            ->assertSee('Fora de validade')
            ->assertSee('300 g')
            ->assertSee('Repor')
            ->assertSee('Vencido')
            ->assertSee('Próximo do vencimento');
    }

    public function test_production_form_lists_recipe_ingredients_with_only_unexpired_available_stock(): void
    {
        $user = User::factory()->create();
        $farinha = Ingrediente::query()->create([
            'nome' => 'Farinha',
            'quantidade_estoque' => '5000.000',
            'unidade_medida' => 'g',
            'estoque_minimo' => '100.000',
            'unidade_embalagem' => 'g',
            'status_ingrediente' => 'ativo',
            'preco_embalagem' => '5.00',
            'tamanho_embalagem' => '1000.000',
        ]);
        $leite = Ingrediente::query()->create([
            'nome' => 'Leite',
            'quantidade_estoque' => '300.000',
            'unidade_medida' => 'ml',
            'estoque_minimo' => '100.000',
            'unidade_embalagem' => 'ml',
            'status_ingrediente' => 'ativo',
            'preco_embalagem' => '3.00',
            'tamanho_embalagem' => '1000.000',
        ]);
        $produto = Produto::query()->create([
            'nome' => 'Bolo de teste',
            'quantidade_estoque' => '0.000',
            'descricao' => 'Produto de teste.',
            'preco_venda' => '10.00',
        ]);
        $receita = Receita::query()->create([
            'produto_id' => $produto->id,
            'descricao' => 'Receita de teste.',
            'custo_total' => '0.00',
        ]);
        $receita->itens()->createMany([
            ['ingrediente_id' => $farinha->id, 'quantidade_necessaria' => '1500.000'],
            ['ingrediente_id' => $leite->id, 'quantidade_necessaria' => '500.000'],
        ]);
        $compra = Compra::query()->create([
            'usuario_id' => $user->id,
            'data_compra' => now(),
            'valor_total' => '12.00',
        ]);
        LoteIngrediente::query()->create([
            'compra_id' => $compra->id,
            'ingrediente_id' => $farinha->id,
            'quantidade_inicial' => '2000.000',
            'quantidade_atual' => '2000.000',
            'preco_custo' => '8.00',
            'data_validade' => today()->addDays(10),
        ]);
        LoteIngrediente::query()->create([
            'compra_id' => $compra->id,
            'ingrediente_id' => $farinha->id,
            'quantidade_inicial' => '3000.000',
            'quantidade_atual' => '3000.000',
            'preco_custo' => '3.00',
            'data_validade' => today()->subDay(),
        ]);
        LoteIngrediente::query()->create([
            'compra_id' => $compra->id,
            'ingrediente_id' => $leite->id,
            'quantidade_inicial' => '400.000',
            'quantidade_atual' => '400.000',
            'preco_custo' => '1.00',
            'data_validade' => today()->addDays(20),
        ]);

        $this->actingAs($user)
            ->get(route('productions.create'))
            ->assertOk()
            ->assertSee('data-production-form', false)
            ->assertSee('Disponibilidade dos ingredientes')
            ->assertSee('dados-disponibilidade-producao', false)
            ->assertSee('min="1"', false)
            ->assertSee('Informe um número inteiro maior que zero.')
            ->assertSeeInOrder([
                'A gravação é transacional',
                'Receita / produto',
            ], false)
            ->assertViewHas('disponibilidadeReceitas', function (array $disponibilidade) use ($receita): bool {
                $itens = collect($disponibilidade[$receita->id])->keyBy('nome');

                return $itens->count() === 2
                    && $itens['Farinha']['quantidade_por_lote'] === 1500.0
                    && $itens['Farinha']['quantidade_disponivel'] === 2000.0
                    && $itens['Leite']['quantidade_por_lote'] === 500.0
                    && $itens['Leite']['quantidade_disponivel'] === 300.0;
            });

        $this->post(route('productions.store'), [
            'receita_id' => $receita->id,
            'quantidade_lotes' => -1,
        ])->assertSessionHasErrors('quantidade_lotes')
            ->assertRedirect(route('productions.create'));
        $this->assertDatabaseCount('producoes', 0);
    }

    public function test_recipe_cost_is_calculated_from_ingredient_unit_cost_and_recalculated_when_package_price_changes(): void
    {
        $user = User::factory()->create();
        $ingrediente = Ingrediente::query()->create([
            'nome' => 'Farinha',
            'quantidade_estoque' => '5000.000',
            'unidade_medida' => 'kg',
            'estoque_minimo' => '1.000',
            'unidade_embalagem' => 'g',
            'status_ingrediente' => 'ativo',
            'preco_embalagem' => '25.00',
            'tamanho_embalagem' => '5000.000',
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
                ['ingrediente_id' => $ingrediente->id, 'quantidade_necessaria' => '0.250'],
            ],
        ]);

        $receita = Receita::query()->sole();
        $response->assertRedirect(route('recipes.show', $receita))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('receitas', [
            'id' => $receita->id,
            'custo_total' => '1.25',
        ]);
        $this->assertDatabaseHas('itens_receita', [
            'receita_id' => $receita->id,
            'ingrediente_id' => $ingrediente->id,
            'quantidade_necessaria' => '250.000',
        ]);

        $this->put(route('ingredients.update', $ingrediente), [
            'nome' => 'Farinha',
            'unidade_medida' => 'kg',
            'estoque_minimo' => '0.100',
            'unidade_embalagem' => 'g',
            'status_ingrediente' => 'ativo',
            'preco_embalagem' => '50.00',
            'tamanho_embalagem' => '5000.000',
        ])->assertRedirect(route('ingredients.show', $ingrediente))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('ingredientes', [
            'id' => $ingrediente->id,
            'estoque_minimo' => '100.000',
            'custo_unitario' => '0.0100',
        ]);
        $this->assertDatabaseHas('receitas', [
            'id' => $receita->id,
            'custo_total' => '2.50',
        ]);
    }

    public function test_recipe_rejects_fractional_quantities_for_unit_ingredients(): void
    {
        $user = User::factory()->create();
        $produto = Produto::query()->create([
            'nome' => 'Omelete',
            'quantidade_estoque' => '0.000',
            'descricao' => 'Omelete simples',
            'preco_venda' => '15.00',
        ]);
        $ingrediente = Ingrediente::query()->create([
            'nome' => 'Ovos',
            'quantidade_estoque' => '12.000',
            'unidade_medida' => 'un',
            'estoque_minimo' => '2.000',
            'unidade_embalagem' => 'un',
            'status_ingrediente' => 'ativo',
            'preco_embalagem' => '12.00',
            'tamanho_embalagem' => '6.000',
        ]);
        $this->actingAs($user);

        $this->post(route('recipes.store'), [
            'produto_id' => $produto->id,
            'descricao' => 'Omelete com ovos',
            'itens' => [
                ['ingrediente_id' => $ingrediente->id, 'quantidade_necessaria' => '1.5'],
            ],
        ])->assertSessionHasErrors('itens.0.quantidade_necessaria');
        $this->assertDatabaseCount('receitas', 0);
        $this->assertDatabaseCount('itens_receita', 0);

        $response = $this->post(route('recipes.store'), [
            'produto_id' => $produto->id,
            'descricao' => 'Omelete com ovos',
            'itens' => [
                ['ingrediente_id' => $ingrediente->id, 'quantidade_necessaria' => '2'],
            ],
        ]);

        $receita = Receita::query()->sole();
        $response->assertRedirect(route('recipes.show', $receita))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('itens_receita', [
            'receita_id' => $receita->id,
            'ingrediente_id' => $ingrediente->id,
            'quantidade_necessaria' => '2.000',
        ]);
    }

    public function test_ingredient_packaging_unit_must_match_the_measurement_type(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('ingredients.create'))
            ->post(route('ingredients.store'), [
                'nome' => 'Farinha',
                'quantidade_inicial' => '1',
                'unidade_medida' => 'kg',
                'estoque_minimo' => '0',
                'unidade_embalagem' => 'ml',
                'status_ingrediente' => 'ativo',
                'preco_embalagem' => '10.00',
                'tamanho_embalagem' => '1',
            ]);

        $response->assertRedirect(route('ingredients.create'))
            ->assertSessionHasErrors('unidade_embalagem');
        $this->assertDatabaseCount('ingredientes', 0);
    }

    public function test_existing_ingredient_cannot_change_its_measurement_type(): void
    {
        $user = User::factory()->create();
        $ingrediente = Ingrediente::query()->create([
            'nome' => 'Farinha',
            'quantidade_estoque' => '1500.000',
            'unidade_medida' => 'g',
            'estoque_minimo' => '100.000',
            'unidade_embalagem' => 'g',
            'status_ingrediente' => 'ativo',
            'preco_embalagem' => '10.00',
            'tamanho_embalagem' => '1000.000',
        ]);

        $response = $this->actingAs($user)
            ->from(route('ingredients.edit', $ingrediente))
            ->put(route('ingredients.update', $ingrediente), [
                'nome' => 'Farinha',
                'unidade_medida' => 'ml',
                'estoque_minimo' => '100',
                'unidade_embalagem' => 'ml',
                'status_ingrediente' => 'ativo',
                'preco_embalagem' => '10.00',
                'tamanho_embalagem' => '1000',
            ]);

        $response->assertRedirect(route('ingredients.edit', $ingrediente))
            ->assertSessionHasErrors([
                'unidade_medida' => 'Não é possível alterar o tipo de medida de um ingrediente já cadastrado.',
            ]);
        $this->assertDatabaseHas('ingredientes', [
            'id' => $ingrediente->id,
            'unidade_medida' => 'g',
            'quantidade_estoque' => '1500.000',
        ]);
    }

    public function test_production_deducts_ingredients_adds_products_and_records_both_movements(): void
    {
        $user = User::factory()->create();
        $ingrediente = Ingrediente::query()->create([
            'nome' => 'Farinha',
            'quantidade_estoque' => '0.000',
            'unidade_medida' => 'kg',
            'estoque_minimo' => '500.000',
            'unidade_embalagem' => 'g',
            'status_ingrediente' => 'ativo',
            'preco_embalagem' => '25.00',
            'tamanho_embalagem' => '5000',
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
            'quantidade_necessaria' => '250.000',
        ]);

        $this->actingAs($user)->post(route('purchases.store'), [
            'data_compra' => now()->toDateString(),
            'itens' => [
                [
                    'ingrediente_id' => $ingrediente->id,
                    'quantidade' => '2',
                    'preco_custo' => '25.00',
                    'data_validade' => now()->addDays(30)->toDateString(),
                ],
            ],
        ])->assertRedirect(route('purchases.index'));

        $response = $this->post(route('productions.store'), [
            'receita_id' => $receita->id,
            'quantidade_lotes' => 2,
        ]);

        $producao = Producao::query()->sole();
        $response->assertRedirect(route('productions.show', $producao))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('ingredientes', [
            'id' => $ingrediente->id,
            'quantidade_estoque' => '1500.000',
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
            'quantidade' => '500.000',
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
            'quantidade_estoque' => '300.000',
            'unidade_medida' => 'kg',
            'estoque_minimo' => '100.000',
            'unidade_embalagem' => 'g',
            'status_ingrediente' => 'ativo',
            'preco_embalagem' => '25.00',
            'tamanho_embalagem' => '5000',
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
            'quantidade_necessaria' => '250.000',
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
            'quantidade_estoque' => '300.000',
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
            'quantidade_estoque' => '1000.000',
            'unidade_medida' => 'kg',
            'estoque_minimo' => '200.000',
            'unidade_embalagem' => 'g',
            'status_ingrediente' => 'ativo',
            'preco_embalagem' => '25.00',
            'tamanho_embalagem' => '5000',
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
            'quantidade_estoque' => '1000.000',
        ]);
        $this->assertDatabaseCount('ajustes_estoque', 0);

        $adjustmentDate = now()->subDay()->startOfMinute();
        $response = $this->actingAs($user)->post(route('stock-adjustments.store'), [
            'ajustavel_type' => Ingrediente::class,
            'ajustavel_id' => $ingrediente->id,
            'tipo_ajuste' => 'entrada',
            'quantidade' => '0.500',
            'justificativa' => 'Recebimento de compra',
            'data_ajuste' => $adjustmentDate->format('Y-m-d\TH:i'),
        ]);

        $ajuste = AjusteEstoque::query()->sole();
        $response->assertRedirect(route('stock-adjustments.show', $ajuste))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('ingredientes', [
            'id' => $ingrediente->id,
            'quantidade_estoque' => '1500.000',
        ]);
        $this->assertDatabaseHas('ajustes_estoque', [
            'id' => $ajuste->id,
            'usuario_id' => $user->id,
            'ajustavel_type' => Ingrediente::class,
            'ajustavel_id' => $ingrediente->id,
            'justificativa' => 'Recebimento de compra',
            'quantidade' => '500.000',
            'data_ajuste' => $adjustmentDate->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_manual_stock_adjustment_rejects_a_future_adjustment_date(): void
    {
        $user = User::factory()->create();
        $produto = Produto::query()->create([
            'nome' => 'Brigadeiro',
            'quantidade_estoque' => '6.000',
            'descricao' => 'Brigadeiro tradicional',
            'preco_venda' => '3.50',
        ]);

        $response = $this->actingAs($user)
            ->from(route('stock-adjustments.create'))
            ->post(route('stock-adjustments.store'), [
                'ajustavel_type' => Produto::class,
                'ajustavel_id' => $produto->id,
                'tipo_ajuste' => 'entrada',
                'quantidade' => '2',
                'justificativa' => 'Entrada registrada para teste.',
                'data_ajuste' => now()->addDay()->format('Y-m-d\TH:i'),
            ]);

        $response->assertRedirect(route('stock-adjustments.create'))
            ->assertSessionHasErrors([
                'data_ajuste' => 'A data e hora do ajuste não podem ser futuras.',
            ]);
        $this->assertDatabaseCount('ajustes_estoque', 0);
        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'quantidade_estoque' => '6.000',
        ]);
    }

    public function test_manual_stock_adjustments_reject_fractional_product_and_unit_ingredient_quantities(): void
    {
        $user = User::factory()->create();
        $produto = Produto::query()->create([
            'nome' => 'Brigadeiro',
            'quantidade_estoque' => '6.000',
            'descricao' => 'Brigadeiro tradicional',
            'preco_venda' => '3.50',
        ]);
        $ingrediente = Ingrediente::query()->create([
            'nome' => 'Ovos',
            'quantidade_estoque' => '4.000',
            'unidade_medida' => 'un',
            'estoque_minimo' => '1.000',
            'unidade_embalagem' => 'un',
            'status_ingrediente' => 'ativo',
            'preco_embalagem' => '12.00',
            'tamanho_embalagem' => '6.000',
        ]);
        $this->actingAs($user);

        $this->post(route('stock-adjustments.store'), [
            'ajustavel_type' => Produto::class,
            'ajustavel_id' => $produto->id,
            'tipo_ajuste' => 'entrada',
            'quantidade' => '0.5',
            'justificativa' => 'Entrada de teste',
        ])->assertSessionHasErrors('quantidade');

        $this->post(route('stock-adjustments.store'), [
            'ajustavel_type' => Ingrediente::class,
            'ajustavel_id' => $ingrediente->id,
            'tipo_ajuste' => 'entrada',
            'quantidade' => '0.5',
            'justificativa' => 'Entrada de teste',
        ])->assertSessionHasErrors('quantidade');

        $this->assertDatabaseCount('ajustes_estoque', 0);
        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'quantidade_estoque' => '6.000',
        ]);
        $this->assertDatabaseHas('ingredientes', [
            'id' => $ingrediente->id,
            'quantidade_estoque' => '4.000',
        ]);
    }

    public function test_order_deducts_stock_and_cancellation_restores_it_once(): void
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
            'status_pedido' => 'Aguardando Pagamento',
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

        $this->post(route('orders.cancel', $pedido))
            ->assertRedirect(route('orders.show', $pedido))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'quantidade_estoque' => '5.000',
        ]);

        $this->post(route('orders.cancel', $pedido))
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

    public function test_pdf_reports_download_valid_pdf_files(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'admin@recipetool.com')->sole();
        $receita = Receita::query()->firstOrFail();
        $this->travelTo(now()->setDate(2026, 11, 18)->setTime(12, 0));

        Producao::query()->create([
            'usuario_id' => $user->id,
            'receita_id' => $receita->id,
            'quantidade_lotes' => 2,
            'data_producao' => now(),
        ]);

        $this->actingAs($user);

        foreach ([
            'reports.executive.pdf' => 'relatorio-executivo-ate-2026-11-18.pdf',
            'reports.production.pdf' => 'relatorio-producao-periodo-completo.pdf',
            'reports.ingredient-consumption.pdf' => 'relatorio-consumo-ingredientes-periodo-completo.pdf',
        ] as $routeName => $filename) {
            $response = $this->get(route($routeName));

            $response->assertOk()
                ->assertDownload($filename)
                ->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-', $response->getContent());
        }
    }

    public function test_pdf_report_period_must_end_on_or_after_its_start_date(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('reports.production.pdf', [
                'de' => '2026-07-02',
                'ate' => '2026-07-01',
            ]))
            ->assertSessionHasErrors('ate');
    }

    public function test_guests_are_redirected_from_pdf_reports(): void
    {
        foreach ([
            'reports.executive.pdf',
            'reports.production.pdf',
            'reports.ingredient-consumption.pdf',
        ] as $routeName) {
            $this->get(route($routeName))
                ->assertRedirect(route('login'));
        }
    }

    public function test_report_center_applies_supported_periods_to_download_links(): void
    {
        $user = User::factory()->create();
        $this->travelTo(now()->setDate(2026, 11, 17)->setTime(12, 0));

        foreach ([
            'dia' => ['2026-11-17', '2026-11-17'],
            'mes_atual' => ['2026-11-01', '2026-11-17'],
            'mes_anterior' => ['2026-10-01', '2026-10-31'],
            'ano' => ['2026-01-01', '2026-11-17'],
        ] as $periodo => [$de, $ate]) {
            $response = $this->actingAs($user)
                ->get(route('reports.index', ['periodo' => $periodo]));

            $response->assertOk()
                ->assertSee('Relatórios')
                ->assertSee(route('reports.production.pdf', ['de' => $de, 'ate' => $ate]))
                ->assertSee(route('reports.ingredient-consumption', ['de' => $de, 'ate' => $ate]));
        }
    }

    public function test_report_center_applies_a_custom_period_to_download_links(): void
    {
        $user = User::factory()->create();
        $this->travelTo(now()->setDate(2026, 11, 17)->setTime(12, 0));

        $this->actingAs($user)
            ->get(route('reports.index', [
                'periodo' => 'personalizado',
                'de' => '2026-10-05',
                'ate' => '2026-10-12',
            ]))
            ->assertOk()
            ->assertSee(route('reports.production.pdf', [
                'de' => '2026-10-05',
                'ate' => '2026-10-12',
            ]));
    }

    public function test_report_center_requires_a_valid_custom_date_range(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('reports.index', [
                'periodo' => 'personalizado',
                'de' => '2026-11-10',
                'ate' => '2026-11-09',
            ]))
            ->assertSessionHasErrors('ate');
    }

    public function test_production_and_consumption_exports_only_include_the_selected_period(): void
    {
        $user = User::factory()->create();
        $ingrediente = Ingrediente::query()->create([
            'nome' => 'Farinha do período',
            'quantidade_estoque' => '1000.000',
            'unidade_medida' => 'g',
            'estoque_minimo' => '100.000',
            'unidade_embalagem' => 'g',
            'status_ingrediente' => 'ativo',
            'preco_embalagem' => '10.00',
            'tamanho_embalagem' => '1000.000',
        ]);
        $produto = Produto::query()->create([
            'nome' => 'Bolo do período',
            'quantidade_estoque' => '0.000',
            'descricao' => 'Produto para testar o filtro de período',
            'preco_venda' => '10.00',
        ]);
        $receita = Receita::query()->create([
            'produto_id' => $produto->id,
            'descricao' => 'Receita do período',
            'custo_total' => '2.00',
        ]);
        ItemReceita::query()->create([
            'receita_id' => $receita->id,
            'ingrediente_id' => $ingrediente->id,
            'quantidade_necessaria' => '100.000',
        ]);
        Producao::query()->create([
            'usuario_id' => $user->id,
            'receita_id' => $receita->id,
            'quantidade_lotes' => 3,
            'data_producao' => '2026-10-12 12:00:00',
        ]);
        Producao::query()->create([
            'usuario_id' => $user->id,
            'receita_id' => $receita->id,
            'quantidade_lotes' => 2,
            'data_producao' => '2026-09-12 12:00:00',
        ]);
        $this->actingAs($user);

        $this->get(route('reports.production', [
            'de' => '2026-10-01',
            'ate' => '2026-10-31',
        ]))
            ->assertDownload('relatorio-producao-2026-10-01-a-2026-10-31.csv')
            ->assertStreamedContent("Produto;\"Quantidade produzida\"\n\"Bolo do período\";3\n");

        $this->get(route('reports.ingredient-consumption', [
            'de' => '2026-10-01',
            'ate' => '2026-10-31',
        ]))
            ->assertDownload('relatorio-consumo-ingredientes-2026-10-01-a-2026-10-31.csv')
            ->assertStreamedContent("Ingrediente;\"Unidade de medida\";\"Quantidade consumida\"\n\"Farinha do período\";g;300\n");

        $this->get(route('reports.production.pdf', [
            'de' => '2026-10-01',
            'ate' => '2026-10-31',
        ]))
            ->assertDownload('relatorio-producao-2026-10-01-a-2026-10-31.pdf');

        $this->get(route('reports.ingredient-consumption.pdf', [
            'de' => '2026-10-01',
            'ate' => '2026-10-31',
        ]))
            ->assertDownload('relatorio-consumo-ingredientes-2026-10-01-a-2026-10-31.pdf');
    }

    public function test_report_center_rejects_future_dates(): void
    {
        $user = User::factory()->create();
        $this->travelTo(now()->setDate(2026, 11, 17)->setTime(12, 0));

        $this->actingAs($user)
            ->get(route('reports.index', [
                'periodo' => 'personalizado',
                'de' => '2026-11-18',
                'ate' => '2026-11-19',
            ]))
            ->assertSessionHasErrors(['de', 'ate']);
    }
}
