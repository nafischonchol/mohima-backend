<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($brand) {
            if (empty($brand->slug)) {
                $brand->slug = Str::slug($brand->name);
            }
        });

        static::updating(function ($brand) {
            if (empty($brand->slug)) {
                $brand->slug = Str::slug($brand->name);
            }
        });

        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('brands_index_' . md5(''));
            \Illuminate\Support\Facades\Cache::forget('popular_brands_8');
        });

        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('brands_index_' . md5(''));
            \Illuminate\Support\Facades\Cache::forget('popular_brands_8');
        });
    }
}


