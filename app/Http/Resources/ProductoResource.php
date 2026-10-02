<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'codigo_barras'       => $this->codigo_barras,
            'nombre'              => $this->nombre,
            'categoria'           => $this->whenLoaded('categoria', fn () => [
                'id'   => $this->categoria->id,
                'name' => $this->categoria->name,
                'slug' => $this->categoria->slug,
            ]),
            'proveedor'           => $this->whenLoaded('proveedor', fn () => [
                'id'     => $this->proveedor->id,
                'nit'    => $this->proveedor->nit,
                'nombre' => $this->proveedor->nombre,
            ]),
            'imagen_url'          => $this->imagen ? Storage::url($this->imagen) : null,
            'image_hash'          => $this->image_hash,
            'stock_actual'        => (int) $this->stock_actual,
            'stock_minimo'        => (int) $this->stock_minimo,
            'es_stock_bajo'       => $this->stock_actual <= $this->stock_minimo,
            'precio_compra'       => (float) $this->precio_compra,
            'porcentaje_ganancia' => (float) $this->porcentaje_ganancia,
            'precio_venta'        => (float) $this->precio_venta,
            'is_active'           => (bool) $this->is_active,
            'created_at'          => $this->created_at?->toISOString(),
            'updated_at'          => $this->updated_at?->toISOString(),
        ];
    }
}
