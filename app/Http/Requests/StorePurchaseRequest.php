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
            'proveedor_id'           => ['required', 'exists:proveedores,id'],
            'numero_factura'         => ['required', 'string', 'max:100', 'unique:compras,numero_factura'],
            'fecha'                  => ['nullable', 'date'],
            'observaciones'          => ['nullable', 'string', 'max:1000'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.producto_id'    => ['required', 'exists:productos,id'],
            'items.*.cantidad'       => ['required', 'integer', 'min:1'],
            'items.*.precio_unitario'=> ['required', 'numeric', 'min:0'],
        ];
    }
}
