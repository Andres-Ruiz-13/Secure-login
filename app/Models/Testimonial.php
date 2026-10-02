<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'position_or_company',
        'testimonial',
        'photo',
        'rating',
        'position',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rating'    => 'integer',
    ];
}
