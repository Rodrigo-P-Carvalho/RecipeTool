<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historico_status_pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status_pedido');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['pedido_id', 'created_at']);
        });

        DB::table('pedidos')
            ->orderBy('id')
            ->chunkById(200, function ($pedidos): void {
                foreach ($pedidos as $pedido) {
                    DB::table('historico_status_pedidos')->insert([
                        'pedido_id' => $pedido->id,
                        'usuario_id' => null,
                        'status_pedido' => $pedido->status_pedido,
                        'created_at' => $pedido->updated_at ?? $pedido->data_pedido,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('historico_status_pedidos');
    }
};
