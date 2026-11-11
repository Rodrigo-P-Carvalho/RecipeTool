<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    public const STATUS_AGUARDANDO_PAGAMENTO = 'Aguardando Pagamento';

    public const STATUS_PAGO = 'Pago';

    public const STATUS_EM_SEPARACAO = 'Em Separação';

    public const STATUS_ENVIADO = 'Enviado';

    public const STATUS_ENTREGUE = 'Entregue';

    public const STATUS_CANCELADO = 'Cancelado';

    public const STATUS_TRANSICOES = [
        self::STATUS_AGUARDANDO_PAGAMENTO,
        self::STATUS_PAGO,
        self::STATUS_EM_SEPARACAO,
        self::STATUS_ENVIADO,
        self::STATUS_ENTREGUE,
    ];

    public const STATUS_FILTRO = [
        ...self::STATUS_TRANSICOES,
        self::STATUS_CANCELADO,
    ];

    protected $table = 'pedidos';

    protected $fillable = [
        'usuario_id',
        'data_pedido',
        'status_pedido',
        'valor_total',
    ];

    protected function casts(): array
    {
        return [
            'data_pedido' => 'datetime',
            'valor_total' => 'decimal:2',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id', 'id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ItemPedido::class, 'pedido_id');
    }

    public function historicoStatus(): HasMany
    {
        return $this->hasMany(HistoricoStatusPedido::class, 'pedido_id')
            ->latest('created_at')
            ->latest('id');
    }
}
