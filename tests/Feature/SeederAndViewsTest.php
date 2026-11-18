<?php

namespace Tests\Feature;

use App\Models\Compra;
use App\Models\Ingrediente;
use App\Models\LoteIngrediente;
use App\Models\Pedido;
use App\Models\Producao;
use App\Models\Produto;
use App\Models\Receita;
use App\Models\User;
use Database\Seeders\DemonstracaoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederAndViewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_the_admin_and_linked_recipe_catalog(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@recipetool.com')->sole();

        $this->assertTrue(Hash::check('password', $admin->password));
        $this->assertSame('admin', $admin->role);
        $this->assertDatabaseCount('ingredientes', 8);
        $this->assertDatabaseCount('produtos', 2);
        $this->assertDatabaseCount('receitas', 2);
        $this->assertDatabaseCount('itens_receita', 10);
        $this->assertDatabaseHas('receitas', [
            'produto_id' => Produto::query()->where('nome', 'Bolo de Chocolate')->value('id'),
            'custo_total' => '14.28',
        ]);
        $this->assertDatabaseHas('ingredientes', [
            'nome' => 'Leite Condensado',
            'custo_unitario' => '0.0164',
        ]);
        $this->assertDatabaseHas('ingredientes', [
            'nome' => 'Farinha de Trigo',
            'quantidade_estoque' => '5000.000',
            'unidade_embalagem' => 'kg',
            'tamanho_embalagem' => '1.000',
            'custo_unitario' => '0.0060',
        ]);
        $this->assertDatabaseHas('ingredientes', [
            'nome' => 'Leite Integral',
            'quantidade_estoque' => '5000.000',
            'unidade_embalagem' => 'l',
            'tamanho_embalagem' => '1.000',
            'custo_unitario' => '0.0055',
        ]);
        $this->assertDatabaseHas('itens_receita', [
            'ingrediente_id' => Ingrediente::query()->where('nome', 'Farinha de Trigo')->value('id'),
            'quantidade_necessaria' => '250.000',
        ]);
    }

    public function test_dashboard_displays_the_update_time_in_brasilia_timezone(): void
    {
        $this->travelTo(Carbon::parse('2026-11-18 02:14:00', 'UTC'));

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Atualizado em 17/11/2026 23:14');
    }

    public function test_demo_seeder_populates_inventory_activity_and_is_safe_to_run_twice(): void
    {
        $this->seed(DemonstracaoSeeder::class);

        $this->assertDatabaseCount('compras', 5);
        $this->assertDatabaseCount('lotes_ingredientes', 86);
        $this->assertDatabaseCount('producoes', 24);
        $this->assertGreaterThanOrEqual(20, Pedido::query()->count());
        $this->assertGreaterThan(150, DB::table('ajustes_estoque')->count());
        $this->assertDatabaseHas('lotes_ingredientes', [
            'codigo_lote' => 'DEMO-CREME-VENCIDO',
            'quantidade_atual' => '400.000',
        ]);
        $this->assertDatabaseHas('lotes_ingredientes', [
            'codigo_lote' => 'DEMO-LEITE-VENCE-EM-5-DIAS',
            'quantidade_atual' => '7550.000',
        ]);

        $farinha = Ingrediente::query()->where('nome', 'Farinha de Trigo')->firstOrFail();
        $this->assertEquals(
            (float) $farinha->lotes()->sum('quantidade_atual'),
            (float) $farinha->quantidade_estoque,
        );
        $estoqueFarinha = $farinha->quantidade_estoque;
        $quantidadePedidos = Pedido::query()->count();

        $this->seed();

        $this->assertDatabaseCount('compras', 5);
        $this->assertDatabaseCount('lotes_ingredientes', 86);
        $this->assertDatabaseCount('producoes', 24);
        $this->assertSame($quantidadePedidos, Pedido::query()->count());
        $this->assertSame(
            $estoqueFarinha,
            Ingrediente::query()->where('nome', 'Farinha de Trigo')->value('quantidade_estoque'),
        );

        $this->actingAs(User::query()->where('role', 'admin')->firstOrFail())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Painel executivo')
            ->assertSee('Pasta Americana')
            ->assertSee('DEMO-CREME-VENCIDO')
            ->assertSee('Volume de produção')
            ->assertSee('Itens que precisam de atenção');
    }

    public function test_demo_seeder_does_not_overwrite_a_database_with_existing_operations(): void
    {
        $this->seed();
        $user = User::query()->where('role', 'admin')->firstOrFail();
        $ingrediente = Ingrediente::query()->firstOrFail();
        $ingrediente->update(['quantidade_estoque' => '1234.000']);
        Producao::query()->create([
            'usuario_id' => $user->id,
            'receita_id' => Receita::query()->firstOrFail()->id,
            'quantidade_lotes' => 1,
            'data_producao' => now(),
        ]);

        $this->seed(DemonstracaoSeeder::class);

        $this->assertDatabaseCount('ingredientes', 8);
        $this->assertDatabaseCount('compras', 0);
        $this->assertDatabaseCount('lotes_ingredientes', 0);
        $this->assertDatabaseHas('ingredientes', [
            'id' => $ingrediente->id,
            'quantidade_estoque' => '1234.000',
        ]);
    }

    public function test_authenticated_users_can_open_all_requested_inventory_and_production_pages(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'admin@recipetool.com')->sole();
        $ingrediente = Ingrediente::query()->firstOrFail();
        $produto = Produto::query()->firstOrFail();
        $receita = Receita::query()->firstOrFail();
        $producao = Producao::query()->create([
            'usuario_id' => $user->id,
            'receita_id' => $receita->id,
            'quantidade_lotes' => 1,
            'data_producao' => now(),
        ]);
        $ajuste = $ingrediente->ajustesEstoque()->create([
            'usuario_id' => $user->id,
            'data_ajuste' => now(),
            'tipo_ajuste' => 'entrada',
            'quantidade' => '1.000',
            'justificativa' => 'Teste de renderização.',
        ]);

        $this->actingAs($user);
        $urls = [
            route('ingredients'),
            route('ingredients.create'),
            route('ingredients.show', $ingrediente),
            route('ingredients.edit', $ingrediente),
            route('products'),
            route('products.create'),
            route('products.show', $produto),
            route('products.edit', $produto),
            route('recipes'),
            route('recipes.create'),
            route('recipes.show', $receita),
            route('recipes.edit', $receita),
            route('productions.index'),
            route('productions.create'),
            route('productions.show', $producao),
            route('orders.index'),
            route('stock-adjustments.index'),
            route('stock-adjustments.create'),
            route('stock-adjustments.show', $ajuste),
            route('stock.index'),
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('RecipeTool')
            ->assertSee($user->name)
            ->assertSee($user->email)
            ->assertSee('Navegação principal')
            ->assertSee('no-underline')
            ->assertDontSee('bootstrap.min.css')
            ->assertSee('Estoque de ingredientes')
            ->assertSee('Estoque de produtos')
            ->assertSee(route('stock-adjustments.index'))
            ->assertSee(route('users'))
            ->assertSee(route('logout'));
    }

    public function test_executive_dashboard_aggregates_inventory_profitability_production_and_alerts(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'admin@recipetool.com')->sole();
        $ingredientes = Ingrediente::query()->get();

        foreach ($ingredientes as $ingrediente) {
            $ingrediente->update([
                'quantidade_estoque' => '0.000',
                'status_ingrediente' => 'inativo',
            ]);
        }

        $ingrediente = Ingrediente::query()->where('nome', 'Farinha de Trigo')->firstOrFail();
        $ingrediente->update([
            'quantidade_estoque' => '500.000',
            'status_ingrediente' => 'ativo',
        ]);
        $receitas = Receita::query()->with('produto')->get();

        foreach ($receitas as $receita) {
            $receita->update([
                'custo_total' => number_format((float) $receita->produto->preco_venda / 2, 2, '.', ''),
            ]);
        }

        $receita = $receitas->firstOrFail();
        Producao::query()->create([
            'usuario_id' => $user->id,
            'receita_id' => $receita->id,
            'quantidade_lotes' => 2,
            'data_producao' => now(),
        ]);
        $compra = Compra::query()->create([
            'usuario_id' => $user->id,
            'data_compra' => now(),
            'valor_total' => '1.00',
        ]);
        LoteIngrediente::query()->create([
            'compra_id' => $compra->id,
            'ingrediente_id' => $ingrediente->id,
            'codigo_lote' => 'DASH-EXPIRING',
            'quantidade_inicial' => '100.000',
            'quantidade_atual' => '100.000',
            'preco_custo' => '1.00',
            'data_validade' => today()->addDays(3),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Custo em estoque')
            ->assertSee('R$ 3,00')
            ->assertSee('Margem média estimada')
            ->assertSee('50,0%')
            ->assertSee('Alertas de validade')
            ->assertSee('Estoque baixo')
            ->assertSee('Custo estimado vs. preço de venda')
            ->assertSee('Bolo de Chocolate')
            ->assertSee('Composição do custo em estoque')
            ->assertSee('por ingrediente')
            ->assertSee('Volume de produção')
            ->assertSee('últimos 12 meses')
            ->assertSee('Itens que precisam de atenção')
            ->assertSee('DASH-EXPIRING')
            ->assertSee('Estoque baixo');
    }

    public function test_orders_page_shows_the_order_management_actions(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'admin@recipetool.com')->sole();

        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Pedidos e vendas')
            ->assertSee('Acompanhe pedidos, valores e situação das vendas.')
            ->assertSee(route('orders.create'));
    }

    public function test_purchase_form_places_add_button_after_items_and_shows_remove_controls(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'admin@recipetool.com')->sole();

        $response = $this->actingAs($user)
            ->get(route('purchases.create'));

        $response->assertOk()
            ->assertSee('Adicionar ingrediente')
            ->assertSee('Remover ingrediente')
            ->assertSee('aria-label="Remover ingrediente"', false)
            ->assertSee('disabled', false);

        $content = $response->getContent();
        $templatePosition = strpos($content, '<template id="purchase-item-template"');
        $visibleForm = substr($content, 0, $templatePosition);
        $lastItemPosition = strrpos($visibleForm, '</article>');
        $addButtonPosition = strpos($visibleForm, 'id="add-purchase-item"');

        $this->assertLessThan($addButtonPosition, $lastItemPosition);
    }

    public function test_transaction_date_inputs_limit_selection_to_the_present(): void
    {
        $this->travelTo(now()->startOfMinute());
        $this->seed();
        $user = User::query()->where('email', 'admin@recipetool.com')->sole();

        $purchaseResponse = $this->actingAs($user)
            ->get(route('purchases.create'))
            ->assertOk()
            ->assertSee('name="data_compra" type="date"', false)
            ->assertSee('max="'.now()->toDateString().'"', false)
            ->assertSee('name="itens[0][data_validade]" type="date"', false);

        preg_match(
            '/<input\b[^>]*name="itens\[0\]\[data_validade\]"[^>]*>/s',
            $purchaseResponse->getContent(),
            $expirationInput,
        );
        $this->assertCount(1, $expirationInput);
        $this->assertStringNotContainsString('max=', $expirationInput[0]);

        $this->get(route('productions.create'))
            ->assertOk()
            ->assertSee('name="data_producao" type="datetime-local"', false)
            ->assertSee('max="'.now()->format('Y-m-d\TH:i').'"', false);

        $this->get(route('stock-adjustments.create'))
            ->assertOk()
            ->assertSee('name="data_ajuste" type="datetime-local"', false)
            ->assertSee('max="'.now()->format('Y-m-d\TH:i').'"', false);
    }

    public function test_stock_dashboard_highlights_ingredients_at_or_below_the_minimum(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'admin@recipetool.com')->sole();
        $ingrediente = Ingrediente::query()->where('nome', 'Farinha de Trigo')->firstOrFail();
        $ingrediente->update(['quantidade_estoque' => $ingrediente->estoque_minimo]);

        $response = $this->actingAs($user)->get(route('stock.index'));

        $response->assertOk()
            ->assertSee('bg-red-50')
            ->assertSee('text-red-900')
            ->assertSee('Farinha de Trigo');
    }

    public function test_inventory_lists_can_be_searched_by_item_name(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'admin@recipetool.com')->sole();

        $this->actingAs($user);

        $this->get(route('ingredients', ['search' => 'farinha']))
            ->assertOk()
            ->assertSee('Farinha de Trigo')
            ->assertDontSee('Leite Condensado')
            ->assertSee('value="farinha"', false);

        $this->post(route('ingredients.search'), ['search' => 'farinha'])
            ->assertRedirect(route('ingredients'));
        $this->get(route('ingredients'))
            ->assertOk()
            ->assertSee('Farinha de Trigo')
            ->assertDontSee('Leite Condensado')
            ->assertSee('method="POST"', false)
            ->assertSee(route('ingredients.search'));

        $this->get(route('products', ['search' => 'bolo']))
            ->assertOk()
            ->assertSee('Bolo de Chocolate')
            ->assertDontSee('Caixa de Brigadeiros');

        $this->post(route('products.search'), ['search' => 'bolo'])
            ->assertRedirect(route('products'));
        $this->get(route('products'))
            ->assertOk()
            ->assertSee('Bolo de Chocolate')
            ->assertDontSee('Caixa de Brigadeiros')
            ->assertSee(route('products.search'));

        $this->get(route('recipes', ['search' => 'caixa']))
            ->assertOk()
            ->assertSee('Caixa de Brigadeiros')
            ->assertDontSee('Bolo de Chocolate');

        $this->post(route('recipes.search'), ['search' => 'caixa'])
            ->assertRedirect(route('recipes'));
        $this->get(route('recipes'))
            ->assertOk()
            ->assertSee('Caixa de Brigadeiros')
            ->assertDontSee('Bolo de Chocolate')
            ->assertSee(route('recipes.search'));
    }

    public function test_inventory_pagination_preserves_search_filters(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'admin@recipetool.com')->sole();

        foreach (range(1, 21) as $numero) {
            Ingrediente::query()->create([
                'nome' => "Ingrediente adicional {$numero}",
                'quantidade_estoque' => '10.000',
                'unidade_medida' => 'g',
                'estoque_minimo' => '1.000',
                'unidade_embalagem' => 'g',
                'status_ingrediente' => 'ativo',
                'preco_embalagem' => '10.00',
                'tamanho_embalagem' => '1000.000',
            ]);
        }

        $this->actingAs($user)
            ->get(route('ingredients', ['search' => 'Ingrediente adicional']))
            ->assertOk()
            ->assertSee('Pagination Navigation')
            ->assertViewHas('ingredientes', function ($ingredientes): bool {
                parse_str(parse_url($ingredientes->nextPageUrl(), PHP_URL_QUERY), $query);

                return $ingredientes->total() === 21
                    && ($query['search'] ?? null) === 'Ingrediente adicional'
                    && ($query['page'] ?? null) === '2';
            });
    }

    public function test_stock_dashboard_searches_each_list_and_keeps_both_filters_in_pagination(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'admin@recipetool.com')->sole();

        foreach (range(1, 31) as $numero) {
            Ingrediente::query()->create([
                'nome' => "Ingrediente adicional {$numero}",
                'quantidade_estoque' => '10.000',
                'unidade_medida' => 'g',
                'estoque_minimo' => '1.000',
                'unidade_embalagem' => 'g',
                'status_ingrediente' => 'ativo',
                'preco_embalagem' => '10.00',
                'tamanho_embalagem' => '1000.000',
            ]);
            Produto::query()->create([
                'nome' => "Produto adicional {$numero}",
                'quantidade_estoque' => '0.000',
                'descricao' => 'Produto de teste.',
                'preco_venda' => '10.00',
            ]);
        }

        $this->actingAs($user)
            ->get(route('stock.index', [
                'busca_ingrediente' => 'Ingrediente adicional',
                'busca_produto' => 'produto adicional',
            ]))
            ->assertOk()
            ->assertSee('Ingrediente adicional 1')
            ->assertDontSee('Leite Condensado')
            ->assertSee('value="Ingrediente adicional"', false)
            ->assertViewHas('ingredientes', function ($ingredientes): bool {
                parse_str(parse_url($ingredientes->nextPageUrl(), PHP_URL_QUERY), $query);

                return $ingredientes->total() === 31
                    && ($query['busca_ingrediente'] ?? null) === 'Ingrediente adicional'
                    && ($query['busca_produto'] ?? null) === 'produto adicional'
                    && ($query['ingredientes_page'] ?? null) === '2';
            })
            ->assertViewHas('produtos', function ($produtos): bool {
                parse_str(parse_url($produtos->nextPageUrl(), PHP_URL_QUERY), $query);

                return $produtos->total() === 31
                    && ($query['busca_ingrediente'] ?? null) === 'Ingrediente adicional'
                    && ($query['busca_produto'] ?? null) === 'produto adicional'
                    && ($query['produtos_page'] ?? null) === '2';
            });
    }

    public function test_stock_search_uses_post_and_keeps_filters_out_of_the_url_and_pagination_links(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'admin@recipetool.com')->sole();

        foreach (range(1, 31) as $numero) {
            Produto::query()->create([
                'nome' => "Produto adicional {$numero}",
                'quantidade_estoque' => '0.000',
                'descricao' => 'Produto de teste.',
                'preco_venda' => '10.00',
            ]);
        }

        $this->actingAs($user)
            ->post(route('stock.search'), [
                'busca_ingrediente' => 'farinha',
                'busca_produto' => 'produto adicional',
            ])
            ->assertRedirect(route('stock.index'));

        $this->get(route('stock.index'))
            ->assertOk()
            ->assertSee('Farinha de Trigo')
            ->assertDontSee('Leite Condensado')
            ->assertSee('value="farinha"', false)
            ->assertSee(route('stock.search'))
            ->assertViewHas('produtos', function ($produtos): bool {
                parse_str(parse_url($produtos->nextPageUrl(), PHP_URL_QUERY), $query);

                return $produtos->total() === 31
                    && ! isset($query['busca_ingrediente'])
                    && ! isset($query['busca_produto'])
                    && ($query['produtos_page'] ?? null) === '2';
            });

        $this->post(route('stock.search'), [
            'busca_ingrediente' => 'farinha',
            'busca_produto' => 'produto adicional',
            'clear' => 'busca_ingrediente',
        ])->assertRedirect(route('stock.index'));

        $this->get(route('stock.index'))
            ->assertOk()
            ->assertSee('Leite Condensado')
            ->assertSee('Produto adicional 1')
            ->assertDontSee('Bolo de Chocolate');
    }

    public function test_production_feedback_uses_bootstrap_success_and_error_alerts(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'admin@recipetool.com')->sole();
        $receita = Receita::query()->firstOrFail();
        $ingrediente = $receita->itens()->firstOrFail()->ingrediente;
        $ingrediente->update(['quantidade_estoque' => '0.000']);

        $this->actingAs($user)
            ->from(route('productions.create'))
            ->post(route('productions.store'), [
                'receita_id' => $receita->id,
                'quantidade_lotes' => 1,
            ])
            ->assertRedirect(route('productions.create'));

        $this->get(route('productions.create'))
            ->assertOk()
            ->assertSee('bg-red-50')
            ->assertSee('A produção não pode ser registrada por falta de estoque válido. Confira a disponibilidade dos ingredientes na lista.')
            ->assertDontSee("Estoque insuficiente para o ingrediente {$ingrediente->nome}.");

        $itensCompra = $receita->itens()
            ->with('ingrediente')
            ->get()
            ->map(fn ($item): array => [
                'ingrediente_id' => $item->ingrediente_id,
                'quantidade' => $item->ingrediente->unidade_medida->converterDaBase(
                    (float) $item->quantidade_necessaria,
                ),
                'preco_custo' => '10.00',
                'data_validade' => now()->addDays(30)->toDateString(),
            ])
            ->all();

        $this->post(route('purchases.store'), [
            'data_compra' => now()->toDateString(),
            'itens' => $itensCompra,
        ])->assertRedirect(route('purchases.index'));

        $response = $this->post(route('productions.store'), [
            'receita_id' => $receita->id,
            'quantidade_lotes' => 1,
        ]);
        $producao = Producao::query()->sole();
        $response->assertRedirect(route('productions.show', $producao));

        $this->get(route('productions.show', $producao))
            ->assertOk()
            ->assertSee('bg-emerald-50')
            ->assertSee('Produção registrada e estoques atualizados com sucesso.');
    }
}
