<?php

namespace App\Models;

use App\Enums\UnidadeMedida;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class Ingrediente extends ItemEstoque
{
    protected $table = 'ingredientes';

    protected $fillable = [
        'nome',
        'quantidade_estoque',
        'unidade_medida',
        'estoque_minimo',
        'unidade_embalagem',
        'status_ingrediente',
        'preco_embalagem',
        'tamanho_embalagem',
    ];

    protected function casts(): array
    {
        return [
            'unidade_embalagem' => UnidadeMedida::class,
            'unidade_medida' => UnidadeMedida::class,
            'quantidade_estoque' => 'decimal:3',
            'estoque_minimo' => 'decimal:3',
            'preco_embalagem' => 'decimal:2',
            'tamanho_embalagem' => 'decimal:3',
            'custo_unitario' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $ingrediente): void {
            $ingrediente->calcularCustoUnitario();
        });
    }

    public function calcularCustoUnitario(): void
    {
        $unidadeEmbalagem = $this->unidade_embalagem;
        $unidadeMedida = $this->unidade_medida;

        if (! $unidadeEmbalagem instanceof UnidadeMedida || ! $unidadeMedida instanceof UnidadeMedida) {
            throw new LogicException('As unidades de embalagem e de medida devem ser válidas.');
        }

        if ($unidadeEmbalagem->tipo() !== $unidadeMedida->tipo()) {
            throw new LogicException('A unidade da embalagem deve ser compatível com a unidade de medida.');
        }

        $tamanhoBase = (float) $this->tamanho_embalagem * $unidadeEmbalagem->fatorParaBase();

        if ($tamanhoBase <= 0) {
            throw new LogicException('O tamanho da embalagem deve ser maior que zero.');
        }

        $this->custo_unitario = number_format(
            round((float) $this->preco_embalagem / $tamanhoBase, 4),
            4,
            '.',
            '',
        );
    }

    protected function custoFormatado(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $unidade = $this->unidade_medida;
                $custo = (float) $this->custo_unitario * $unidade->fatorParaBase();

                return 'R$ '.self::formatarValor($custo, 4).' / '.$unidade->value;
            },
        );
    }

    protected function quantidadeEstoqueFormatada(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->formatarQuantidadeComUnidadeDinamica(
                (float) $this->quantidade_estoque,
            ),
        );
    }

    protected function estoqueMinimoFormatado(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->formatarQuantidadeComUnidadeDinamica(
                (float) $this->estoque_minimo,
            ),
        );
    }

    protected function estoqueMinimoNaUnidade(): Attribute
    {
        return Attribute::make(
            get: fn (): string => self::formatarDecimal(
                $this->unidade_medida->converterDaBase((float) $this->estoque_minimo),
            ),
        );
    }

    protected function tamanhoEmbalagemFormatado(): Attribute
    {
        return Attribute::make(
            get: fn (): string => self::formatarQuantidade((float) $this->tamanho_embalagem)
                .' '.$this->unidade_embalagem->value,
        );
    }

    public function itensReceita(): HasMany
    {
        return $this->hasMany(ItemReceita::class, 'ingrediente_id');
    }

    public function lotes(): HasMany
    {
        return $this->hasMany(LoteIngrediente::class, 'ingrediente_id');
    }

    private static function formatarQuantidade(float $quantidade): string
    {
        return self::formatarDecimal($quantidade);
    }

    private function formatarQuantidadeComUnidadeDinamica(float $quantidadeBase): string
    {
        $unidadeBase = UnidadeMedida::from($this->unidade_medida->unidadeBase());
        $unidadeExibicao = match ($unidadeBase) {
            UnidadeMedida::G => $quantidadeBase >= 1000 ? UnidadeMedida::Kg : UnidadeMedida::G,
            UnidadeMedida::Ml => $quantidadeBase >= 1000 ? UnidadeMedida::L : UnidadeMedida::Ml,
            UnidadeMedida::Un => UnidadeMedida::Un,
            default => throw new LogicException('A unidade base do ingrediente não é válida.'),
        };

        return self::formatarQuantidade($unidadeExibicao->converterDaBase($quantidadeBase))
            .' '.$unidadeExibicao->value;
    }

    private static function formatarDecimal(float $quantidade): string
    {
        return rtrim(rtrim(number_format($quantidade, 2, ',', '.'), '0'), ',');
    }

    private static function formatarValor(float $valor, int $casasDecimais): string
    {
        $valorFormatado = number_format($valor, $casasDecimais, ',', '.');
        $valorFormatado = rtrim(rtrim($valorFormatado, '0'), ',');

        if (! str_contains($valorFormatado, ',')) {
            $valorFormatado .= ',00';
        } elseif (strlen(substr(strrchr($valorFormatado, ','), 1)) < 2) {
            $valorFormatado .= '0';
        }

        return $valorFormatado;
    }
}
