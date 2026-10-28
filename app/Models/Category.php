<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getNombreAttribute(): ?string
    {
        return $this->name;
    }

    public function setNombreAttribute(?string $value): void
    {
        $this->attributes['name'] = $value;
        if (empty($this->attributes['slug']) && ! empty($value)) {
            $this->attributes['slug'] = \Illuminate\Support\Str::slug($value);
        }
    }

    public function getDescripcionAttribute(): ?string
    {
        return $this->description;
    }

    public function setDescripcionAttribute(?string $value): void
    {
        $this->attributes['description'] = $value;
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'categoria_id');
    }
}
