<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cria os registros de produção associados a usuários e receitas.
     */
    public function up(): void
    {
        Schema::create('producoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('receita_id')->constrained('receitas')->restrictOnDelete();
            $table->integer('quantidade_lotes');
            $table->timestamp('data_producao');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producoes');
    }
};
