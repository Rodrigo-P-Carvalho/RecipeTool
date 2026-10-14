<?php

namespace Tests\Feature;

use App\Models\Ingrediente;
use App\Models\Producao;
use App\Models\Produto;
use App\Models\Receita;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_orders_page_shows_a_coming_soon_placeholder(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'admin@recipetool.com')->sole();

        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Pedidos e vendas')
            ->assertSee('Estamos preparando esta área')
            ->assertSee('Em breve')
            ->assertSee(route('dashboard'))
            ->assertSee(route('stock.index'));
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

        $this->get(route('products', ['search' => 'bolo']))
            ->assertOk()
            ->assertSee('Bolo de Chocolate')
            ->assertDontSee('Caixa de Brigadeiros');

        $this->get(route('recipes', ['search' => 'caixa']))
            ->assertOk()
            ->assertSee('Caixa de Brigadeiros')
            ->assertDontSee('Bolo de Chocolate');
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
                'status_ingrediente' => 'ativo',
                'preco_embalagem' => '10.00',
                'tamanho_embalagem' => '1000.000',
                'custo_unitario' => '0.0100',
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
            Produto::query()->create([
                'nome' => "Produto adicional {$numero}",
                'quantidade_estoque' => '0.000',
                'descricao' => 'Produto de teste.',
                'preco_venda' => '10.00',
            ]);
        }

        $this->actingAs($user)
            ->get(route('stock.index', [
                'busca_ingrediente' => 'farinha',
                'busca_produto' => 'produto adicional',
            ]))
            ->assertOk()
            ->assertSee('Farinha de Trigo')
            ->assertDontSee('Leite Condensado')
            ->assertSee('value="farinha"', false)
            ->assertViewHas('produtos', function ($produtos): bool {
                parse_str(parse_url($produtos->nextPageUrl(), PHP_URL_QUERY), $query);

                return $produtos->total() === 31
                    && ($query['busca_ingrediente'] ?? null) === 'farinha'
                    && ($query['busca_produto'] ?? null) === 'produto adicional'
                    && ($query['produtos_page'] ?? null) === '2';
            });
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
            ->assertSee("Estoque insuficiente para o ingrediente {$ingrediente->nome}.");

        $ingrediente->update(['quantidade_estoque' => '5000.000']);

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
