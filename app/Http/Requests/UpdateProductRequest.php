<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $producto = $this->route('producto');

        return $this->user() && $this->user()->can('update', $producto);
    }

    public function rules(): array
    {
        $productoId = $this->route('producto') instanceof \App\Models\Producto
            ? $this->route('producto')->id
            : $this->route('producto');

        return [
            'codigo_barras'       => ['sometimes', 'required', 'string', 'max:100', Rule::unique('productos', 'codigo_barras')->ignore($productoId)],
            'nombre'              => ['sometimes', 'required', 'string', 'max:255'],
            'categoria_id'        => ['nullable', 'exists:categories,id'],
            'proveedor_id'        => ['nullable', 'exists:proveedores,id'],
            'stock_actual'        => ['nullable', 'integer', 'min:0'],
            'stock_minimo'        => ['nullable', 'integer', 'min:0'],
            'precio_compra'       => ['sometimes', 'required', 'numeric', 'min:0'],
            'porcentaje_ganancia' => ['nullable', 'numeric', 'min:0'],
            'imagen'              => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
