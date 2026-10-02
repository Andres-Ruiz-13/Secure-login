<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'provider',
        'video_url',
        'video_id',
        'thumbnail',
        'position',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Retorna la URL de incrustación segura para YouTube o Vimeo.
     */
    public function getEmbedUrlAttribute(): string
    {
        return match (strtolower($this->provider)) {
            'vimeo'   => "https://player.vimeo.com/video/{$this->video_id}",
            default   => "https://www.youtube.com/embed/{$this->video_id}",
        };
    }
}
