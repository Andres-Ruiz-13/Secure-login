<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashSession extends Model
{
    use HasFactory;

    protected $table = 'cash_sessions';

    protected $fillable = [
        'cash_register_id',
        'user_id',
        'saldo_inicial',
        'saldo_final_esperado',
        'saldo_final_contado',
        'diferencia',
        'estado',
        'opened_at',
        'closed_at',
        'observaciones_apertura',
        'observaciones_cierre',
    ];

    protected $casts = [
        'saldo_inicial' => 'float',
        'saldo_final_esperado' => 'float',
        'saldo_final_contado' => 'float',
        'diferencia' => 'float',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class, 'cash_register_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'cash_session_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class, 'cash_session_id');
    }

    public function getSaldoFinalRealAttribute(): ?float
    {
        return $this->saldo_final_contado;
    }

    public function getAperturaAtAttribute(): ?\Illuminate\Support\Carbon
    {
        return $this->opened_at;
    }

    public function scopeAbierta($query)
    {
        return $query->where('estado', 'abierta');
    }
}

