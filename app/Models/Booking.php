<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'booking_code',
        'user_id',
        'service_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'vehicle_type',
        'vehicle_plate',
        'vehicle_color',
        'booking_date',
        'booking_time',
        'status',
        'total_price',
        'notes',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'total_price'  => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'     => 'Pendiente',
            'confirmed'   => 'Confirmada',
            'in_progress' => 'En Lavado',
            'completed'   => 'Listo / Entregado',
            'cancelled'   => 'Cancelada',
            default       => ucfirst($this->status),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'pending'     => 'bg-warning text-dark',
            'confirmed'   => 'bg-primary text-white',
            'in_progress' => 'bg-info text-dark',
            'completed'   => 'bg-success text-white',
            'cancelled'   => 'bg-danger text-white',
            default       => 'bg-secondary text-white',
        };
    }

    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->total_price, 0, ',', '.');
    }
}
