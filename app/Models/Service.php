<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'duration_minutes',
        'icon',
        'media_id',
        'active',
    ];

    protected $casts = [
        'price'            => 'decimal:2',
        'duration_minutes' => 'integer',
        'active'           => 'boolean',
    ];

    public function media()
    {
        return $this->belongsTo(Media::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->price, 0, ',', '.');
    }
}
