<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemNotification extends Model
{
    use HasFactory;

    protected $table = 'system_notifications';

    protected $fillable = [
        'user_id',
        'target_role',
        'titulo',
        'mensaje',
        'tipo',
        'icono',
        'action_url',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeParaRol($query, string $rol)
    {
        return $query->where(function ($q) use ($rol) {
            $q->where('target_role', 'todos')
              ->orWhere('target_role', $rol);
        });
    }

    public function scopeNoLeidas($query)
    {
        return $query->where('is_read', false);
    }
}

