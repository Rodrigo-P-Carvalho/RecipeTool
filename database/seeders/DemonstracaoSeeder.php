<?php

namespace Database\Seeders;

use App\Models\AjusteEstoque;
use App\Models\Compra;
use App\Models\HistoricoStatusPedido;
use App\Models\Ingrediente;
use App\Models\ItemReceita;
use App\Models\LoteIngrediente;
use App\Models\Pedido;
use App\Models\Producao;
use App\Models\Produto;
use App\Models\Receita;
use App\Models\User;
use App\Services\CompraService;
use App\Services\LoteIngredienteService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DemonstracaoSeeder extends Seeder
{
    public const MARCADOR = 'DEMO_SEED: RecipeTool v1';

    /**
     * Popula o sistema com um conjunto integrado e repetível de dados de demonstração.
     */
    public function run(CompraService $compraService, LoteIngredienteService $loteService): void
    {
        if (Compra::query()->where('observacoes', 'like', self::MARCADOR.'%')->exists()) {
            $this->command?->info('O conjunto de demonstração já foi criado; nenhuma alteração foi feita.');

            return;
        }

        if (
            Compra::query()->exists()
            || LoteIngrediente::query()->exists()
            || Producao::query()->exists()
            || Pedido::query()->exists()
            || AjusteEstoque::query()->exists()
        ) {
            $this->command?->warn(
                'Seeder de demonstração ignorado para preservar operações existentes no banco.',
            );

            return;
        }

        if (
            ! User::query()->where('role', 'admin')->exists()
            || ! Ingrediente::query()->exists()
            || ! Produto::query()->exists()
        ) {
            $this->call([
                UsuarioSeeder::class,
                IngredienteSeeder::class,
                ProdutoSeeder::class,
                ReceitaSeeder::class,
            ]);
        }

        DB::transaction(function () use ($compraService, $loteService): void {
            $usuario = User::query()->where('role', 'admin')->firstOrFail();
            $ingredientes = $this->criarIngredientes();
            $produtos = $this->criarProdutos();
            $receitas = $this->criarReceitas($ingredientes, $produtos);

            $this->registrarCompras($compraService, $usuario, $ingredientes);
            $this->registrarProducoes($usuario, $ingredientes, $produtos, $receitas, $loteService);
            $this->registrarPedidos($usuario, $produtos);
            $this->registrarAjustesManuais($usuario, $ingredientes, $produtos, $loteService);
        });
    }

    /**
     * @return array<string, Ingrediente>
     */
    private function criarIngredientes(): array
    {
        $catalogo = [
            'Leite Condensado' => ['g', 'g', '395.000', '6.49', '1000.000'],
            'Farinha de Trigo' => ['g', 'kg', '1.000', '5.99', '3000.000'],
            'Chocolate em Pó' => ['g', 'g', '500.000', '14.90', '1000.000'],
            'Açúcar Refinado' => ['g', 'kg', '1.000', '5.49', '2500.000'],
            'Ovos' => ['un', 'un', '12.000', '10.80', '24.000'],
            'Leite Integral' => ['ml', 'l', '1.000', '5.49', '1500.000'],
            'Manteiga' => ['g', 'g', '200.000', '10.99', '1200.000'],
            'Creme de Leite' => ['g', 'g', '200.000', '3.49', '1000.000'],
            'Essência de Baunilha' => ['g', 'g', '30.000', '9.90', '200.000'],
            'Fermento Químico' => ['g', 'g', '100.000', '5.90', '250.000'],
            'Bicarbonato de Sódio' => ['g', 'g', '100.000', '4.50', '200.000'],
            'Coco Ralado' => ['g', 'g', '100.000', '4.90', '300.000'],
            'Doce de Leite' => ['g', 'g', '400.000', '8.50', '500.000'],
            'Morango' => ['g', 'kg', '1.000', '18.00', '1500.000'],
            'Creme de Avelã' => ['g', 'g', '350.000', '22.00', '500.000'],
            'Granulado' => ['g', 'g', '100.000', '4.00', '250.000'],
            'Forminhas de Papel' => ['un', 'un', '100.000', '12.00', '60.000'],
            'Caixa para Doces' => ['un', 'un', '50.000', '35.00', '15.000'],
            'Limão' => ['un', 'un', '10.000', '8.00', '20.000'],
            'Cream Cheese' => ['g', 'g', '300.000', '15.90', '700.000'],
            'Cenoura' => ['g', 'kg', '1.000', '5.90', '1000.000'],
            'Biscoito Maisena' => ['g', 'g', '350.000', '5.50', '500.000'],
            'Mel' => ['g', 'g', '300.000', '12.00', '250.000'],
            'Canela em Pó' => ['g', 'g', '50.000', '5.00', '100.000'],
            'Chantilly' => ['ml', 'l', '1.000', '19.90', '700.000'],
            'Corante Alimentício' => ['g', 'g', '30.000', '6.50', '50.000'],
            'Pasta Americana' => ['g', 'g', '500.000', '18.00', '2000.000'],
            'Caixa para Bolo' => ['un', 'un', '25.000', '42.50', '8.000'],
        ];
        $ingredientes = [];

        foreach ($catalogo as $nome => [$unidade, $unidadeEmbalagem, $tamanho, $preco, $estoqueMinimo]) {
            $ingrediente = Ingrediente::query()->firstOrNew(['nome' => $nome]);
            $ingrediente->fill([
                'quantidade_estoque' => '0.000',
                'unidade_medida' => $unidade,
                'estoque_minimo' => $estoqueMinimo,
                'unidade_embalagem' => $unidadeEmbalagem,
                'status_ingrediente' => 'ativo',
                'preco_embalagem' => $preco,
                'tamanho_embalagem' => $tamanho,
            ]);
            $ingrediente->calcularCustoUnitario();
            $ingrediente->save();
            $ingredientes[$nome] = $ingrediente;
        }

        return $ingredientes;
    }

    /**
     * @return array<string, Produto>
     */
    private function criarProdutos(): array
    {
        $catalogo = [
            'Bolo de Chocolate' => ['Bolo macio de chocolate com cobertura cremosa.', '45.00'],
            'Caixa de Brigadeiros (20 unidades)' => ['Caixa com brigadeiros tradicionais para compartilhar.', '30.00'],
            'Brownie de Chocolate' => ['Brownie intenso, úmido por dentro e com casquinha crocante.', '12.00'],
            'Cupcake de Baunilha' => ['Cupcake de baunilha com cobertura e forminha decorada.', '14.00'],
            'Bolo de Cenoura' => ['Bolo de cenoura com cobertura de chocolate.', '42.00'],
            'Cookie de Chocolate' => ['Cookie artesanal com pedaços de chocolate.', '8.50'],
            'Torta de Limão' => ['Torta cremosa de limão com base crocante.', '58.00'],
            'Pão de Mel Recheado' => ['Pão de mel com especiarias, doce de leite e cobertura.', '11.00'],
            'Donut Caseiro' => ['Donut macio com cobertura de chocolate.', '13.00'],
            'Cheesecake de Morango' => ['Cheesecake com cream cheese e calda de morango.', '68.00'],
            'Brigadeiro Gourmet' => ['Brigadeiro gourmet finalizado com granulado.', '5.00'],
            'Caixa de Mini Doces' => ['Caixa presenteável com doces variados.', '42.00'],
            'Bolo Decorado' => ['Bolo de festa decorado com chantilly e pasta americana.', '95.00'],
        ];
        $produtos = [];

        foreach ($catalogo as $nome => [$descricao, $preco]) {
            $produtos[$nome] = Produto::query()->updateOrCreate(
                ['nome' => $nome],
                [
                    'quantidade_estoque' => '0.000',
                    'descricao' => $descricao,
                    'preco_venda' => $preco,
                ],
            );
        }

        return $produtos;
    }

    /**
     * @param  array<string, Ingrediente>  $ingredientes
     * @param  array<string, Produto>  $produtos
     * @return array<string, Receita>
     */
    private function criarReceitas(array $ingredientes, array $produtos): array
    {
        $catalogo = [
            'Brownie de Chocolate' => [
                'descricao' => 'Massa concentrada de chocolate com manteiga e ovos.',
                'itens' => ['Farinha de Trigo' => 180, 'Açúcar Refinado' => 150, 'Ovos' => 2, 'Chocolate em Pó' => 100, 'Manteiga' => 90],
            ],
            'Cupcake de Baunilha' => [
                'descricao' => 'Massa leve de baunilha com cobertura e embalagem individual.',
                'itens' => ['Farinha de Trigo' => 120, 'Açúcar Refinado' => 100, 'Ovos' => 2, 'Manteiga' => 60, 'Leite Integral' => 100, 'Essência de Baunilha' => 5, 'Forminhas de Papel' => 6],
            ],
            'Bolo de Cenoura' => [
                'descricao' => 'Massa de cenoura com cobertura de chocolate.',
                'itens' => ['Farinha de Trigo' => 250, 'Açúcar Refinado' => 180, 'Ovos' => 3, 'Cenoura' => 250, 'Leite Integral' => 100, 'Manteiga' => 60, 'Chocolate em Pó' => 60],
            ],
            'Cookie de Chocolate' => [
                'descricao' => 'Cookie amanteigado com chocolate e leve toque de baunilha.',
                'itens' => ['Farinha de Trigo' => 200, 'Açúcar Refinado' => 120, 'Ovos' => 1, 'Manteiga' => 120, 'Chocolate em Pó' => 80, 'Essência de Baunilha' => 3, 'Bicarbonato de Sódio' => 2],
            ],
            'Torta de Limão' => [
                'descricao' => 'Base de biscoito com creme de limão e cobertura aerada.',
                'itens' => ['Biscoito Maisena' => 200, 'Leite Condensado' => 395, 'Creme de Leite' => 200, 'Manteiga' => 80, 'Limão' => 4],
            ],
            'Pão de Mel Recheado' => [
                'descricao' => 'Massa de mel e canela recheada com doce de leite.',
                'itens' => ['Farinha de Trigo' => 200, 'Açúcar Refinado' => 80, 'Ovos' => 1, 'Mel' => 100, 'Chocolate em Pó' => 35, 'Doce de Leite' => 100, 'Canela em Pó' => 3, 'Fermento Químico' => 5],
            ],
            'Donut Caseiro' => [
                'descricao' => 'Massa fermentada frita e finalizada com chocolate.',
                'itens' => ['Farinha de Trigo' => 300, 'Açúcar Refinado' => 60, 'Ovos' => 1, 'Leite Integral' => 150, 'Manteiga' => 30, 'Fermento Químico' => 8, 'Chocolate em Pó' => 40],
            ],
            'Cheesecake de Morango' => [
                'descricao' => 'Cheesecake assado com cream cheese e morangos.',
                'itens' => ['Cream Cheese' => 300, 'Morango' => 200, 'Açúcar Refinado' => 100, 'Ovos' => 2, 'Biscoito Maisena' => 150, 'Manteiga' => 80],
            ],
            'Brigadeiro Gourmet' => [
                'descricao' => 'Brigadeiro de chocolate enrolado e servido em forminha.',
                'itens' => ['Leite Condensado' => 395, 'Chocolate em Pó' => 60, 'Manteiga' => 20, 'Granulado' => 20, 'Forminhas de Papel' => 20],
            ],
            'Caixa de Mini Doces' => [
                'descricao' => 'Seleção de doces com coco e chocolate em caixa presenteável.',
                'itens' => ['Leite Condensado' => 395, 'Chocolate em Pó' => 50, 'Manteiga' => 15, 'Coco Ralado' => 30, 'Forminhas de Papel' => 20, 'Caixa para Doces' => 1],
            ],
            'Bolo Decorado' => [
                'descricao' => 'Bolo de festa com decoração de chantilly e pasta americana.',
                'itens' => ['Farinha de Trigo' => 250, 'Açúcar Refinado' => 180, 'Ovos' => 3, 'Manteiga' => 80, 'Chantilly' => 250, 'Corante Alimentício' => 2, 'Pasta Americana' => 120, 'Caixa para Bolo' => 1, 'Fermento Químico' => 8],
            ],
        ];
        $receitas = [];

        foreach ($catalogo as $nomeProduto => $dados) {
            $produto = $produtos[$nomeProduto];
            $receita = Receita::query()->updateOrCreate(
                ['produto_id' => $produto->id],
                ['descricao' => $dados['descricao'], 'custo_total' => '0.00'],
            );
            $receita->itens()->delete();

            foreach ($dados['itens'] as $nomeIngrediente => $quantidade) {
                ItemReceita::query()->create([
                    'receita_id' => $receita->id,
                    'ingrediente_id' => $ingredientes[$nomeIngrediente]->id,
                    'quantidade_necessaria' => number_format((float) $quantidade, 3, '.', ''),
                ]);
            }

            $receita->recalcularCusto();
            $receita->load(['itens.ingrediente', 'produto']);
            $receitas[$nomeProduto] = $receita;
        }

        foreach (['Bolo de Chocolate', 'Caixa de Brigadeiros (20 unidades)'] as $nomeProduto) {
            $receitas[$nomeProduto] = Receita::query()
                ->where('produto_id', $produtos[$nomeProduto]->id)
                ->with(['itens.ingrediente', 'produto'])
                ->firstOrFail();
        }

        return $receitas;
    }

    /**
     * @param  array<string, Ingrediente>  $ingredientes
     */
    private function registrarCompras(
        CompraService $compraService,
        User $usuario,
        array $ingredientes,
    ): void {
        $lotes = [
            [
                'data_compra' => today()->subMonths(12),
                'validade' => today()->addMonths(8),
                'custo_multiplicador' => 1,
            ],
            [
                'data_compra' => today()->subMonths(7),
                'validade' => today()->addMonths(5),
                'custo_multiplicador' => 1.04,
            ],
            [
                'data_compra' => today()->subMonths(3),
                'validade' => today()->addMonths(2),
                'custo_multiplicador' => 1.08,
            ],
        ];

        foreach ($lotes as $indice => $lote) {
            $itens = [];

            foreach ($ingredientes as $nome => $ingrediente) {
                $quantidade = $this->quantidadePorCompra($nome, $ingrediente, $indice);
                $precoCusto = max(
                    0.5,
                    round($quantidade * (float) $ingrediente->custo_unitario * $lote['custo_multiplicador'], 2),
                );

                $itens[] = [
                    'ingrediente_id' => $ingrediente->id,
                    'quantidade' => number_format($quantidade, 3, '.', ''),
                    'preco_custo' => number_format($precoCusto, 2, '.', ''),
                    'data_validade' => $lote['validade']->toDateString(),
                    'codigo_lote' => 'DEMO-'.($indice + 1).'-'.strtoupper(substr(sha1($nome), 0, 7)),
                ];
            }

            $compraService->registrarCompra([
                'data_compra' => $lote['data_compra']->toDateString(),
                'observacoes' => self::MARCADOR.': Compra '.($indice + 1).' de 3',
                'itens' => $itens,
            ], $usuario->id);
        }

        $compraService->registrarCompra([
            'data_compra' => today()->subDays(30)->toDateString(),
            'observacoes' => self::MARCADOR.': Lote próximo do vencimento',
            'itens' => [[
                'ingrediente_id' => $ingredientes['Leite Integral']->id,
                'quantidade' => '9000',
                'preco_custo' => '49.41',
                'data_validade' => today()->addDays(5)->toDateString(),
                'codigo_lote' => 'DEMO-LEITE-VENCE-EM-5-DIAS',
            ]],
        ], $usuario->id);

        $compraService->registrarCompra([
            'data_compra' => today()->subDays(20)->toDateString(),
            'observacoes' => self::MARCADOR.': Lote vencido para demonstração de alertas',
            'itens' => [[
                'ingrediente_id' => $ingredientes['Creme de Leite']->id,
                'quantidade' => '400',
                'preco_custo' => '6.98',
                'data_validade' => today()->subDays(3)->toDateString(),
                'codigo_lote' => 'DEMO-CREME-VENCIDO',
            ]],
        ], $usuario->id);
    }

    private function quantidadePorCompra(string $nome, Ingrediente $ingrediente, int $indice): float
    {
        if ($nome === 'Pasta Americana') {
            return 600;
        }

        if ($ingrediente->unidade_medida->value === 'un') {
            return match ($nome) {
                'Ovos' => 60,
                'Forminhas de Papel' => 100,
                'Caixa para Doces' => 20,
                'Caixa para Bolo' => 12,
                default => 30,
            };
        }

        $quantidade = match ($nome) {
            'Farinha de Trigo' => 6000,
            'Açúcar Refinado' => 4500,
            'Chocolate em Pó' => 2500,
            'Leite Condensado' => 2400,
            'Manteiga' => 2500,
            'Creme de Leite' => 2000,
            default => 1800,
        };

        return round($quantidade * [1, 0.9, 1.1][$indice], 3);
    }

    /**
     * @param  array<string, Ingrediente>  $ingredientes
     * @param  array<string, Produto>  $produtos
     * @param  array<string, Receita>  $receitas
     */
    private function registrarProducoes(
        User $usuario,
        array $ingredientes,
        array $produtos,
        array $receitas,
        LoteIngredienteService $loteService,
    ): void {
        $nomesReceitas = array_keys($receitas);
        $indice = 0;

        for ($mesesAtras = 11; $mesesAtras >= 0; $mesesAtras--) {
            for ($producaoNoMes = 0; $producaoNoMes < 2; $producaoNoMes++) {
                $receita = $receitas[$nomesReceitas[$indice % count($nomesReceitas)]];
                $quantidadeLotes = 1 + ($indice % 2);
                $dataProducao = $mesesAtras === 0
                    ? today()->subDay()->setTime(10 + $producaoNoMes, 0)
                    : today()->subMonths($mesesAtras)->startOfMonth()
                        ->addDays(8 + $producaoNoMes * 10)
                        ->setTime(10 + $producaoNoMes, 0);

                $producao = Producao::query()->create([
                    'usuario_id' => $usuario->id,
                    'receita_id' => $receita->id,
                    'quantidade_lotes' => $quantidadeLotes,
                    'data_producao' => $dataProducao,
                ]);

                foreach ($receita->itens as $item) {
                    $ingrediente = $ingredientes[$item->ingrediente->nome];
                    $quantidade = round((float) $item->quantidade_necessaria * $quantidadeLotes, 3);

                    $loteService->consumir($ingrediente, $quantidade, somenteLotesValidos: true);
                    $ingrediente->decrement('quantidade_estoque', $quantidade);
                    $ingrediente->ajustesEstoque()->create([
                        'usuario_id' => $usuario->id,
                        'data_ajuste' => $dataProducao,
                        'tipo_ajuste' => 'producao_saida',
                        'quantidade' => number_format($quantidade, 3, '.', ''),
                        'justificativa' => "Produção de demonstração #{$producao->id}.",
                    ]);
                }

                $produto = $produtos[$receita->produto->nome];
                $produto->increment('quantidade_estoque', $quantidadeLotes);
                $produto->ajustesEstoque()->create([
                    'usuario_id' => $usuario->id,
                    'data_ajuste' => $dataProducao,
                    'tipo_ajuste' => 'producao_entrada',
                    'quantidade' => $quantidadeLotes,
                    'justificativa' => "Produção de demonstração #{$producao->id}.",
                ]);
                $indice++;
            }
        }
    }

    /**
     * @param  array<string, Produto>  $produtos
     */
    private function registrarPedidos(User $usuario, array $produtos): void
    {
        $indice = 0;

        foreach ([1, 2] as $rodada) {
            foreach ($produtos as $produto) {
                $estoqueDisponivel = (int) $produto->fresh()->quantidade_estoque;
                $quantidade = $rodada === 1 ? 1 : ($estoqueDisponivel > 1 ? 1 : 0);

                if ($quantidade === 0 || $estoqueDisponivel < $quantidade) {
                    continue;
                }

                $dataPedido = now()->subDays(max(1, 72 - $indice * 2))->setTime(11, 0);
                $pedido = $this->criarPedido(
                    $usuario,
                    [$produto->id => $quantidade],
                    $dataPedido,
                    $indice,
                );
                $indice++;
            }
        }

        $produto = collect($produtos)
            ->first(fn (Produto $item): bool => (int) $item->fresh()->quantidade_estoque > 0);

        if ($produto instanceof Produto) {
            $this->criarPedido(
                $usuario,
                [$produto->id => 1],
                now()->subDays(2)->setTime(15, 0),
                $indice,
                cancelado: true,
            );
        }
    }

    /**
     * @param  array<int, int>  $itens
     */
    private function criarPedido(
        User $usuario,
        array $itens,
        Carbon $dataPedido,
        int $indice,
        bool $cancelado = false,
    ): Pedido {
        $produtos = Produto::query()->whereIn('id', array_keys($itens))->get()->keyBy('id');
        $valorTotalCentavos = collect($itens)->sum(
            fn (int $quantidade, int $produtoId): int => (int) round((float) $produtos[$produtoId]->preco_venda * 100)
                * $quantidade,
        );
        $statusPorIndice = [
            Pedido::STATUS_AGUARDANDO_PAGAMENTO,
            Pedido::STATUS_PAGO,
            Pedido::STATUS_EM_SEPARACAO,
            Pedido::STATUS_ENVIADO,
            Pedido::STATUS_ENTREGUE,
        ];
        $statusFinal = $cancelado ? Pedido::STATUS_CANCELADO : $statusPorIndice[$indice % count($statusPorIndice)];
        $pedido = Pedido::query()->create([
            'usuario_id' => $usuario->id,
            'data_pedido' => $dataPedido,
            'status_pedido' => $statusFinal,
            'valor_total' => number_format($valorTotalCentavos / 100, 2, '.', ''),
        ]);

        foreach ($itens as $produtoId => $quantidade) {
            $produto = $produtos[$produtoId];
            $pedido->itens()->create([
                'produto_id' => $produtoId,
                'qtd' => $quantidade,
            ]);

            if ($cancelado) {
                continue;
            }

            $produto->decrement('quantidade_estoque', $quantidade);
            $produto->ajustesEstoque()->create([
                'usuario_id' => $usuario->id,
                'data_ajuste' => $dataPedido,
                'tipo_ajuste' => 'venda_saida',
                'quantidade' => $quantidade,
                'justificativa' => "Venda de demonstração no pedido #{$pedido->id}.",
            ]);
        }

        $historico = $cancelado
            ? [Pedido::STATUS_AGUARDANDO_PAGAMENTO, Pedido::STATUS_CANCELADO]
            : array_slice(
                [
                    Pedido::STATUS_AGUARDANDO_PAGAMENTO,
                    Pedido::STATUS_PAGO,
                    Pedido::STATUS_EM_SEPARACAO,
                    Pedido::STATUS_ENVIADO,
                    Pedido::STATUS_ENTREGUE,
                ],
                0,
                array_search($statusFinal, $statusPorIndice, true) + 1,
            );

        foreach ($historico as $passo => $status) {
            HistoricoStatusPedido::query()->create([
                'pedido_id' => $pedido->id,
                'usuario_id' => $usuario->id,
                'status_pedido' => $status,
                'created_at' => $dataPedido->copy()->addHours($passo * 3),
            ]);
        }

        return $pedido;
    }

    /**
     * @param  array<string, Ingrediente>  $ingredientes
     * @param  array<string, Produto>  $produtos
     */
    private function registrarAjustesManuais(
        User $usuario,
        array $ingredientes,
        array $produtos,
        LoteIngredienteService $loteService,
    ): void {
        $desperdicios = [
            'Farinha de Trigo' => 80,
            'Chocolate em Pó' => 60,
            'Manteiga' => 50,
            'Morango' => 100,
        ];

        foreach ($desperdicios as $nome => $quantidade) {
            $ingrediente = $ingredientes[$nome];
            $loteService->consumir($ingrediente, $quantidade, somenteLotesValidos: true);
            $ingrediente->decrement('quantidade_estoque', $quantidade);
            $ingrediente->ajustesEstoque()->create([
                'usuario_id' => $usuario->id,
                'data_ajuste' => now()->subDays(6),
                'tipo_ajuste' => 'saida',
                'quantidade' => $quantidade,
                'justificativa' => 'Perda operacional de demonstração.',
            ]);
        }

        foreach (array_slice($produtos, 0, 4) as $produto) {
            $produto->increment('quantidade_estoque', 1);
            $produto->ajustesEstoque()->create([
                'usuario_id' => $usuario->id,
                'data_ajuste' => now()->subDays(3),
                'tipo_ajuste' => 'entrada',
                'quantidade' => 1,
                'justificativa' => 'Contagem física de demonstração.',
            ]);
        }
    }
}
