<?php

namespace Database\Seeders;

use App\Models\Ingrediente;
use Illuminate\Database\Seeder;

class IngredienteSeeder extends Seeder
{
    public function run(): void
    {
        $ingredientes = [
            [
                'nome' => 'Leite Condensado',
                'quantidade_estoque' => '3950.000',
                'unidade_medida' => 'g',
                'estoque_minimo' => '790.000',
                'unidade_embalagem' => 'g',
                'status_ingrediente' => 'ativo',
                'preco_embalagem' => '6.49',
                'tamanho_embalagem' => '395.000',
            ],
            [
                'nome' => 'Farinha de Trigo',
                'quantidade_estoque' => '5000.000',
                'unidade_medida' => 'g',
                'estoque_minimo' => '1000.000',
                'unidade_embalagem' => 'kg',
                'status_ingrediente' => 'ativo',
                'preco_embalagem' => '5.99',
                'tamanho_embalagem' => '1.000',
            ],
            [
                'nome' => 'Chocolate em Pó',
                'quantidade_estoque' => '1500.000',
                'unidade_medida' => 'g',
                'estoque_minimo' => '250.000',
                'unidade_embalagem' => 'g',
                'status_ingrediente' => 'ativo',
                'preco_embalagem' => '14.90',
                'tamanho_embalagem' => '500.000',
            ],
            [
                'nome' => 'Açúcar Refinado',
                'quantidade_estoque' => '4000.000',
                'unidade_medida' => 'g',
                'estoque_minimo' => '1000.000',
                'unidade_embalagem' => 'kg',
                'status_ingrediente' => 'ativo',
                'preco_embalagem' => '5.49',
                'tamanho_embalagem' => '1.000',
            ],
            [
                'nome' => 'Ovos',
                'quantidade_estoque' => '60.000',
                'unidade_medida' => 'un',
                'estoque_minimo' => '12.000',
                'unidade_embalagem' => 'un',
                'status_ingrediente' => 'ativo',
                'preco_embalagem' => '10.80',
                'tamanho_embalagem' => '12.000',
            ],
            [
                'nome' => 'Leite Integral',
                'quantidade_estoque' => '5000.000',
                'unidade_medida' => 'ml',
                'estoque_minimo' => '1000.000',
                'unidade_embalagem' => 'l',
                'status_ingrediente' => 'ativo',
                'preco_embalagem' => '5.49',
                'tamanho_embalagem' => '1.000',
            ],
            [
                'nome' => 'Manteiga',
                'quantidade_estoque' => '800.000',
                'unidade_medida' => 'g',
                'estoque_minimo' => '200.000',
                'unidade_embalagem' => 'g',
                'status_ingrediente' => 'ativo',
                'preco_embalagem' => '10.99',
                'tamanho_embalagem' => '200.000',
            ],
            [
                'nome' => 'Creme de Leite',
                'quantidade_estoque' => '1000.000',
                'unidade_medida' => 'g',
                'estoque_minimo' => '200.000',
                'unidade_embalagem' => 'g',
                'status_ingrediente' => 'ativo',
                'preco_embalagem' => '3.49',
                'tamanho_embalagem' => '200.000',
            ],
        ];

        foreach ($ingredientes as $ingrediente) {
            $model = Ingrediente::query()->firstOrNew(['nome' => $ingrediente['nome']]);
            $model->fill($ingrediente);
            $model->calcularCustoUnitario();
            $model->save();
        }
    }
}
