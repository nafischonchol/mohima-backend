<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

class Category extends Model
{
    use HasFactory;
    use HasRecursiveRelationships;

    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'icon',
        'is_active',
        'meta_title',
        'meta_keyword',
        'meta_description',
        'meta_image',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'meta_keyword' => 'array',
    ];

    public function slugUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->slug.'-'.$this->id,
        );
    }
    protected static function booted(): void
    {
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('popular_categories');
        });

        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('popular_categories');
        });
    }
}

