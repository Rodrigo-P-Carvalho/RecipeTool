<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cria os itens que relacionam receitas a ingredientes.
     */
    public function up(): void
    {
        Schema::create('itens_receita', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receita_id')->constrained('receitas')->cascadeOnDelete();
            $table->foreignId('ingrediente_id')->constrained('ingredientes')->restrictOnDelete();
            $table->decimal('quantidade_necessaria', 14, 3);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itens_receita');
    }
};
