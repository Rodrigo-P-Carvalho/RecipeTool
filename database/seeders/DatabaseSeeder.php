<?php

namespace Database\Seeders;

use App\Models\AjusteEstoque;
use App\Models\Compra;
use App\Models\LoteIngrediente;
use App\Models\Pedido;
use App\Models\Producao;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (
            Compra::query()->exists()
            || LoteIngrediente::query()->exists()
            || Producao::query()->exists()
            || Pedido::query()->exists()
            || AjusteEstoque::query()->exists()
        ) {
            $this->command?->warn(
                'Seed padrão ignorado porque já existem operações registradas no banco.',
            );

            return;
        }

        $this->call([
            UsuarioSeeder::class,
            IngredienteSeeder::class,
            ProdutoSeeder::class,
            ReceitaSeeder::class,
        ]);
    }
}
