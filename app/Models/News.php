<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $table = 'news';

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'media_id',
        'published',
    ];

    protected $casts = [
        'published' => 'boolean',
    ];

    /**
     * Relationship with Media (Section 5.12)
     */
    public function media()
    {
        return $this->belongsTo(Media::class);
    }
}
