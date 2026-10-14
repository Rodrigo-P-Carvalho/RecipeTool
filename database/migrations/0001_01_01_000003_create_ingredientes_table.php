<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cria ingredientes com os atributos herdados de ItemEstoque.
     */
    public function up(): void
    {
        Schema::create('ingredientes', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->decimal('quantidade_estoque', 14, 3);
            $table->string('unidade_medida');
            $table->decimal('estoque_minimo', 14, 3);
            $table->string('status_ingrediente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredientes');
    }
};
