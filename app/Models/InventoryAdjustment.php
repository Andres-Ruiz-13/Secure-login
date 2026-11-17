<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAdjustment extends Model
{
    use HasFactory;

    protected $table = 'inventory_adjustments';

    protected $fillable = [
        'codigo',
        'producto_id',
        'warehouse_id',
        'existencia_anterior',
        'existencia_nueva',
        'diferencia',
        'motivo',
        'estado',
        'solicitado_por',
        'autorizado_por',
        'autorizado_at',
    ];

    protected $casts = [
        'existencia_anterior' => 'integer',
        'existencia_nueva' => 'integer',
        'diferencia' => 'integer',
        'autorizado_at' => 'datetime',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function autorizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }
}

