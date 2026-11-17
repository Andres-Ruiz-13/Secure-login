<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $table = 'news';

    protected $fillable = [
        'title',
        'slug',
        'category',
        'priority',
        'target_role',
        'excerpt',
        'content',
        'media_id',
        'published',
        'pinned',
        'expires_at',
        'author_id',
    ];

    protected $casts = [
        'published' => 'boolean',
        'pinned' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function media()
    {
        return $this->belongsTo(Media::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function reads()
    {
        return $this->hasMany(NewsRead::class, 'news_id');
    }

    public function scopeActive($query)
    {
        return $query->where('published', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>=', now());
            });
    }

    public function scopeParaRol($query, string $rol)
    {
        return $query->where(function ($q) use ($rol) {
            $q->where('target_role', 'todos')
              ->orWhere('target_role', $rol);
        });
    }

    public function scopePinned($query)
    {
        return $query->where('pinned', true);
    }
}
