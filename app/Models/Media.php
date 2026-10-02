<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $fillable = [
        'name',
        'path',
        'mime_type',
        'size',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'size'   => 'integer',
    ];

    /**
     * Relationship with News (Section 5.12)
     */
    public function news()
    {
        return $this->hasMany(News::class);
    }

    /**
     * Get the public URL for the stored media (Section 5.9)
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->path);
    }

    /**
     * Human-readable file size format
     */
    public function getFormattedSizeAttribute(): string
    {
        if (! $this->size) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = floor(log($this->size, 1024));
        $power = min($power, count($units) - 1);

        return round($this->size / (1024 ** $power), 2) . ' ' . $units[$power];
    }
}
