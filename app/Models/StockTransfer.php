<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    use HasFactory;

    protected $table = 'stock_transfers';

    protected $fillable = [
        'codigo',
        'origen_warehouse_id',
        'destino_warehouse_id',
        'estado',
        'solicitado_por',
        'autorizado_por',
        'recibido_por',
        'observaciones',
        'aprobada_at',
        'recibida_at',
    ];

    protected $casts = [
        'aprobada_at' => 'datetime',
        'recibida_at' => 'datetime',
    ];

    public function origen(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'origen_warehouse_id');
    }

    public function destino(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destino_warehouse_id');
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function autorizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }

    public function receptor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recibido_por');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class, 'transfer_id');
    }
}

