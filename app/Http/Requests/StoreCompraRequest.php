<?php

namespace App\Http\Requests;

use App\Models\Ingrediente;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $ingredientIds = collect($this->input('itens', []))
            ->filter(fn (mixed $item): bool => is_array($item))
            ->pluck('ingrediente_id')
            ->filter()
            ->unique()
            ->values();
        $ingredientIdsWithUnitMeasure = Ingrediente::query()
            ->whereIn('id', $ingredientIds)
            ->where('unidade_medida', 'un')
            ->pluck('id')
            ->map(fn (int|string $id): int => (int) $id)
            ->all();

        return [
            'data_compra' => ['required', 'date'],
            'observacoes' => ['nullable', 'string'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.ingrediente_id' => [
                'required',
                'integer',
                Rule::exists('ingredientes', 'id')->where('status_ingrediente', 'ativo'),
            ],
            'itens.*.quantidade' => [
                'required',
                'decimal:0,3',
                'gt:0',
                function (string $attribute, mixed $value, Closure $fail) use ($ingredientIdsWithUnitMeasure): void {
                    $parts = explode('.', $attribute);
                    $ingredientId = $this->input("itens.{$parts[1]}.ingrediente_id");

                    if (
                        in_array((int) $ingredientId, $ingredientIdsWithUnitMeasure, true)
                        && filter_var($value, FILTER_VALIDATE_INT) === false
                    ) {
                        $fail('A quantidade deste ingrediente deve ser um número inteiro.');
                    }
                },
            ],
            'itens.*.preco_custo' => ['required', 'decimal:0,2', 'min:0'],
            'itens.*.data_validade' => ['required', 'date'],
            'itens.*.codigo_lote' => ['nullable', 'string', 'max:100'],
        ];
    }
}
