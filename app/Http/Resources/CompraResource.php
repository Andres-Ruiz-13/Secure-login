<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'numero_factura' => $this->numero_factura,
            'proveedor'      => $this->whenLoaded('proveedor', fn () => [
                'id'     => $this->proveedor->id,
                'nit'    => $this->proveedor->nit,
                'nombre' => $this->proveedor->nombre,
            ]),
            'usuario'        => $this->whenLoaded('usuario', fn () => [
                'id'   => $this->usuario->id,
                'name' => $this->usuario->name,
            ]),
            'total'          => (float) $this->total,
            'fecha'          => $this->fecha?->toISOString(),
            'observaciones'  => $this->observaciones,
            'detalles'       => $this->whenLoaded('detalles', fn () => $this->detalles->map(fn ($d) => [
                'id'              => $d->id,
                'producto_id'     => $d->producto_id,
                'producto_nombre' => $d->producto?->nombre,
                'cantidad'        => (int) $d->cantidad,
                'precio_unitario' => (float) $d->precio_unitario,
                'subtotal'        => (float) $d->subtotal,
            ])),
            'created_at'     => $this->created_at?->toISOString(),
        ];
    }
}
