<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasRole(['admin', 'bodeguero']);
    }

    public function rules(): array
    {
        return [
            'proveedor_id'                => ['required', 'exists:proveedores,id'],
            'numero_factura'              => ['required', 'string', 'max:100', 'unique:compras,numero_factura'],
            'fecha'                       => ['nullable', 'date'],
            'porcentaje_ganancia'         => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'observaciones'               => ['nullable', 'string', 'max:1000'],
            'items'                       => ['required', 'array', 'min:1'],
            'items.*.tipo'                => ['nullable', 'in:existente,nuevo'],
            'items.*.producto_id'         => ['required_without:items.*.nombre', 'nullable', 'exists:productos,id'],
            'items.*.nombre'              => ['required_without:items.*.producto_id', 'nullable', 'string', 'max:150'],
            'items.*.codigo_barras'       => ['nullable', 'string', 'max:50'],
            'items.*.descripcion'         => ['nullable', 'string', 'max:500'],
            'items.*.categoria_id'        => ['nullable', 'exists:categories,id'],
            'items.*.cantidad'            => ['required', 'integer', 'min:1'],
            'items.*.precio_unitario'     => ['required', 'numeric', 'min:0'],
            'items.*.porcentaje_ganancia' => ['nullable', 'numeric', 'min:0', 'max:1000'],
        ];
    }
}
