<?php

namespace App\Models;

use App\Services\PricingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';

    protected $fillable = [
        'codigo_barras',
        'nombre',
        'categoria_id',
        'proveedor_id',
        'imagen',
        'image_hash',
        'stock_actual',
        'stock_minimo',
        'precio_compra',
        'porcentaje_ganancia',
        'precio_venta',
        'is_active',
    ];

    protected $casts = [
        'stock_actual'        => 'integer',
        'stock_minimo'        => 'integer',
        'precio_compra'       => 'decimal:2',
        'porcentaje_ganancia' => 'decimal:2',
        'precio_venta'        => 'decimal:2',
        'is_active'           => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        // Siempre calcula el precio de venta en backend para garantizar integridad financiera
        static::saving(function (Producto $producto) {
            $producto->precio_venta = PricingService::calculateSalePrice(
                (float) $producto->precio_compra,
                (float) $producto->porcentaje_ganancia
            );
        });
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categoria_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function detallesCompra(): HasMany
    {
        return $this->hasMany(DetalleCompra::class, 'producto_id');
    }

    public function detallesVenta(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'producto_id');
    }

    /**
     * Scope para productos con stock bajo (stock actual <= stock mínimo)
     */
    public function scopeStockBajo(Builder $query): Builder
    {
        return $query->whereColumn('stock_actual', '<=', 'stock_minimo');
    }
}
