<?php

namespace Tests\Feature;

use App\Models\Ingrediente;
use App\Models\ItemPedido;
use App\Models\ItemReceita;
use App\Models\Pedido;
use App\Models\Producao;
use App\Models\Produto;
use App\Models\Receita;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeToolRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_models_resolve_inventory_recipe_order_and_polymorphic_relationships(): void
    {
        $usuario = User::factory()->create();

        $ingrediente = Ingrediente::query()->create([
            'nome' => 'Farinha',
            'quantidade_estoque' => '8500.000',
            'unidade_medida' => 'kg',
            'estoque_minimo' => '1000.000',
            'unidade_embalagem' => 'kg',
            'status_ingrediente' => 'ativo',
            'preco_embalagem' => '25.00',
            'tamanho_embalagem' => '5.000',
        ]);

        $produto = Produto::query()->create([
            'nome' => 'Pão',
            'quantidade_estoque' => '4.000',
            'descricao' => 'Pão artesanal',
            'preco_venda' => '12.50',
        ]);

        $receita = Receita::query()->create([
            'produto_id' => $produto->id,
            'descricao' => 'Receita de pão',
            'custo_total' => '5.25',
        ]);
        $itemReceita = ItemReceita::query()->create([
            'receita_id' => $receita->id,
            'ingrediente_id' => $ingrediente->id,
            'quantidade_necessaria' => '500.000',
        ]);

        $pedido = Pedido::query()->create([
            'usuario_id' => $usuario->id,
            'data_pedido' => now(),
            'status_pedido' => 'aberto',
            'valor_total' => '25.00',
        ]);
        $itemPedido = ItemPedido::query()->create([
            'pedido_id' => $pedido->id,
            'produto_id' => $produto->id,
            'qtd' => 2,
        ]);

        $producao = Producao::query()->create([
            'usuario_id' => $usuario->id,
            'receita_id' => $receita->id,
            'quantidade_lotes' => 2,
            'data_producao' => now(),
        ]);
        $ajusteIngrediente = $ingrediente->ajustesEstoque()->create([
            'usuario_id' => $usuario->id,
            'data_ajuste' => now(),
            'tipo_ajuste' => 'entrada',
            'quantidade' => '1.000',
            'justificativa' => 'Recebimento de compra',
        ]);
        $ajusteProduto = $produto->ajustesEstoque()->create([
            'usuario_id' => $usuario->id,
            'data_ajuste' => now(),
            'tipo_ajuste' => 'saida',
            'quantidade' => '1.000',
            'justificativa' => 'Ajuste de inventário',
        ]);

        $this->assertSame($receita->id, $produto->receita->id);
        $this->assertSame($produto->id, $receita->produto->id);
        $this->assertSame($itemReceita->id, $receita->itens->first()->id);
        $this->assertSame($itemReceita->id, $ingrediente->itensReceita->first()->id);
        $this->assertSame($receita->id, $itemReceita->receita->id);
        $this->assertSame($ingrediente->id, $itemReceita->ingrediente->id);

        $this->assertSame($itemPedido->id, $pedido->itens->first()->id);
        $this->assertSame($pedido->id, $itemPedido->pedido->id);
        $this->assertSame($produto->id, $itemPedido->produto->id);
        $this->assertSame($producao->id, $receita->producoes->first()->id);
        $this->assertSame($receita->id, $producao->receita->id);
        $this->assertSame($usuario->id, $producao->usuario->id);
        $this->assertSame($pedido->id, $usuario->pedidos->first()->id);
        $this->assertSame($producao->id, $usuario->producoes->first()->id);
        $this->assertSame($ajusteIngrediente->id, $usuario->ajustesEstoque->first()->id);

        $this->assertInstanceOf(Ingrediente::class, $ajusteIngrediente->ajustavel);
        $this->assertSame($ingrediente->id, $ajusteIngrediente->ajustavel->id);
        $this->assertSame($ajusteIngrediente->id, $ingrediente->ajustesEstoque->first()->id);
        $this->assertInstanceOf(Produto::class, $ajusteProduto->ajustavel);
        $this->assertSame($produto->id, $ajusteProduto->ajustavel->id);
        $this->assertSame($ajusteProduto->id, $produto->ajustesEstoque->first()->id);
    }
}
