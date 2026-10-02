<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasRole(['admin', 'vendedor', 'bodeguero']);
    }

    public function rules(): array
    {
        return [
            'metodo_pago'        => ['required', 'string', 'in:efectivo,tarjeta,transferencia,credito,otro'],
            'numero_factura'     => ['nullable', 'string', 'max:100', 'unique:ventas,numero_factura'],
            'cliente_nombre'     => ['nullable', 'string', 'max:255'],
            'cliente_documento'  => ['nullable', 'string', 'max:50'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.producto_id'=> ['required', 'exists:productos,id'],
            'items.*.cantidad'   => ['required', 'integer', 'min:1'],
        ];
    }
}
