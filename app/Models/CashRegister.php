<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CashRegister extends Model
{
    use HasFactory;

    protected $table = 'cash_registers';

    protected $fillable = [
        'codigo',
        'nombre',
        'warehouse_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CashSession::class, 'cash_register_id');
    }

    public function currentSession(): HasOne
    {
        return $this->hasOne(CashSession::class, 'cash_register_id')->where('estado', 'abierta')->latest('opened_at');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

