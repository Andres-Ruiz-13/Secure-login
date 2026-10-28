<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo FacturaCompra (Cabecera de compra a proveedor)
 */
class FacturaCompra extends Compra
{
    protected $table = 'compras';

    public function detallesFactura(): HasMany
    {
        return $this->hasMany(DetalleFacturaCompra::class, 'compra_id');
    }
}

