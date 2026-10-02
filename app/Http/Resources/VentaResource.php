<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VentaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'numero_factura'    => $this->numero_factura,
            'usuario'           => $this->whenLoaded('usuario', fn () => [
                'id'   => $this->usuario->id,
                'name' => $this->usuario->name,
            ]),
            'metodo_pago'       => $this->metodo_pago,
            'total'             => (float) $this->total,
            'fecha'             => $this->fecha?->toISOString(),
            'cliente_nombre'    => $this->cliente_nombre,
            'cliente_documento' => $this->cliente_documento,
            'detalles'          => $this->whenLoaded('detalles', fn () => $this->detalles->map(fn ($d) => [
                'id'              => $d->id,
                'producto_id'     => $d->producto_id,
                'producto_nombre' => $d->producto?->nombre,
                'cantidad'        => (int) $d->cantidad,
                'precio_unitario' => (float) $d->precio_unitario,
                'subtotal'        => (float) $d->subtotal,
            ])),
            'created_at'        => $this->created_at?->toISOString(),
        ];
    }
}
