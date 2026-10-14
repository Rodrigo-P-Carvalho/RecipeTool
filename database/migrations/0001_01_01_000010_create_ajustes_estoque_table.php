<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cria ajustes de estoque polimórficos, vinculados a produto ou ingrediente.
     */
    public function up(): void
    {
        Schema::create('ajustes_estoque', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->morphs('ajustavel');
            $table->timestamp('data_ajuste');
            $table->string('tipo_ajuste');
            $table->decimal('quantidade', 14, 3);
            $table->string('justificativa');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajustes_estoque');
    }
};
