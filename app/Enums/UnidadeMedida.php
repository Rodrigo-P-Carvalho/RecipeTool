<?php

namespace App\Enums;

enum UnidadeMedida: string
{
    case G = 'g';
    case Kg = 'kg';
    case Ml = 'ml';
    case L = 'l';
    case Un = 'un';

    public function fatorParaBase(): float
    {
        return match ($this) {
            self::Kg, self::L => 1000.0,
            self::G, self::Ml, self::Un => 1.0,
        };
    }

    public function unidadeBase(): string
    {
        return match ($this) {
            self::G, self::Kg => self::G->value,
            self::Ml, self::L => self::Ml->value,
            self::Un => self::Un->value,
        };
    }

    public function tipo(): string
    {
        return match ($this) {
            self::G, self::Kg => 'massa',
            self::Ml, self::L => 'volume',
            self::Un => 'unidade',
        };
    }

    public function converterParaBase(float $quantidade): float
    {
        return round($quantidade * $this->fatorParaBase(), 3);
    }

    public function converterDaBase(float $quantidade): float
    {
        return $quantidade / $this->fatorParaBase();
    }
}
