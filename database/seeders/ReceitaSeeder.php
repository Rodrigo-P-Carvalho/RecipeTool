<?php

namespace Database\Seeders;

use App\Models\Ingrediente;
use App\Models\Produto;
use App\Models\Receita;
use Illuminate\Database\Seeder;

class ReceitaSeeder extends Seeder
{
    public function run(): void
    {
        $ingredientes = Ingrediente::query()->get()->keyBy('nome');
        $receitas = [
            [
                'produto' => 'Bolo de Chocolate',
                'descricao' => 'Ingredientes para produzir uma unidade de bolo de chocolate.',
                'itens_base' => [
                    'Farinha de Trigo' => '250.000',
                    'Açúcar Refinado' => '200.000',
                    'Ovos' => '3.000',
                    'Chocolate em Pó' => '80.000',
                    'Leite Integral' => '200.000',
                    'Manteiga' => '100.000',
                ],
            ],
            [
                'produto' => 'Caixa de Brigadeiros (20 unidades)',
                'descricao' => 'Ingredientes para produzir uma caixa com vinte brigadeiros.',
                'itens_base' => [
                    'Leite Condensado' => '395.000',
                    'Chocolate em Pó' => '60.000',
                    'Manteiga' => '20.000',
                    'Creme de Leite' => '100.000',
                ],
            ],
        ];

        foreach ($receitas as $dados) {
            $produto = Produto::query()
                ->where('nome', $dados['produto'])
                ->firstOrFail();
            $receita = Receita::query()->updateOrCreate(
                ['produto_id' => $produto->id],
                [
                    'descricao' => $dados['descricao'],
                    'custo_total' => '0.00',
                ],
            );

            $receita->itens()->delete();
            $receita->itens()->createMany(
                collect($dados['itens_base'])->map(
                    fn (string $quantidadeBase, string $nome): array => [
                        'ingrediente_id' => $ingredientes->get($nome)->id,
                        'quantidade_necessaria' => $quantidadeBase,
                    ],
                )->values()->all(),
            );
            $receita->recalcularCusto();
        }
    }
}
