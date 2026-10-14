<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ingredientes', function (Blueprint $table) {
            $table->decimal('preco_embalagem', 12, 2);
            $table->decimal('tamanho_embalagem', 14, 3);
            $table->decimal('custo_unitario', 14, 4);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ingredientes', function (Blueprint $table) {
            $table->dropColumn([
                'preco_embalagem',
                'tamanho_embalagem',
                'custo_unitario',
            ]);
        });
    }
};
