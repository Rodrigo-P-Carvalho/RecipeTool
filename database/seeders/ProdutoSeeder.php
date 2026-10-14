<?php

namespace Database\Seeders;

use App\Models\Produto;
use Illuminate\Database\Seeder;

class ProdutoSeeder extends Seeder
{
    public function run(): void
    {
        $produtos = [
            [
                'nome' => 'Bolo de Chocolate',
                'quantidade_estoque' => '0.000',
                'descricao' => 'Bolo de chocolate caseiro, vendido por unidade.',
                'preco_venda' => '45.00',
            ],
            [
                'nome' => 'Caixa de Brigadeiros (20 unidades)',
                'quantidade_estoque' => '0.000',
                'descricao' => 'Caixa com vinte brigadeiros tradicionais.',
                'preco_venda' => '30.00',
            ],
        ];

        foreach ($produtos as $produto) {
            Produto::query()->updateOrCreate(
                ['nome' => $produto['nome']],
                $produto,
            );
        }
    }
}
