<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AboutSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'content',
        'mission',
        'vision',
        'values',
        'history',
        'primary_image',
        'secondary_image',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
