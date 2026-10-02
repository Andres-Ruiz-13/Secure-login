<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('create', \App\Models\Producto::class);
    }

    public function rules(): array
    {
        return [
            'codigo_barras'       => ['required', 'string', 'max:100', 'unique:productos,codigo_barras'],
            'nombre'              => ['required', 'string', 'max:255'],
            'categoria_id'        => ['nullable', 'exists:categories,id'],
            'proveedor_id'        => ['nullable', 'exists:proveedores,id'],
            'stock_actual'        => ['nullable', 'integer', 'min:0'],
            'stock_minimo'        => ['nullable', 'integer', 'min:0'],
            'precio_compra'       => ['required', 'numeric', 'min:0'],
            'porcentaje_ganancia' => ['nullable', 'numeric', 'min:0'],
            'imagen'              => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
