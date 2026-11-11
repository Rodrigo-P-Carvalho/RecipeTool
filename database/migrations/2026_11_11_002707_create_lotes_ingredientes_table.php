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
        Schema::create('lotes_ingredientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras')->cascadeOnDelete();
            $table->foreignId('ingrediente_id')->constrained('ingredientes')->restrictOnDelete();
            $table->string('codigo_lote')->nullable();
            $table->decimal('quantidade_inicial', 14, 3);
            $table->decimal('quantidade_atual', 14, 3);
            $table->decimal('preco_custo', 12, 2);
            $table->date('data_validade');
            $table->index(['data_validade', 'quantidade_atual']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotes_ingredientes');
    }
};
