<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'itens' => ['required', 'array', 'min:1'],
            'itens.*' => ['required', 'array:produto_id,qtd'],
            'itens.*.produto_id' => ['required', 'integer', 'distinct', 'exists:produtos,id'],
            'itens.*.qtd' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'itens.required' => 'Adicione ao menos um produto ao pedido.',
            'itens.min' => 'Adicione ao menos um produto ao pedido.',
            'itens.*.produto_id.required' => 'Selecione um produto para cada item.',
            'itens.*.produto_id.distinct' => 'Cada produto deve aparecer apenas uma vez no pedido.',
            'itens.*.produto_id.exists' => 'Um dos produtos selecionados não existe.',
            'itens.*.qtd.required' => 'Informe a quantidade de cada produto.',
            'itens.*.qtd.integer' => 'A quantidade deve ser um número inteiro.',
            'itens.*.qtd.min' => 'A quantidade deve ser de pelo menos uma unidade.',
        ];
    }
}
