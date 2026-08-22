<?php

namespace App\Models;

use App\Enums\ProductStatusEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'bangla_name',
        'slug',
        'short_description',
        'description',
        'category_id',
        'brand_id',
        'unit_id',
        'youtube_video_urls',
        'weight',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'meta_image',
        'image',
        'status',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'meta_keywords' => 'array',
        'youtube_video_urls' => 'array',
        'weight' => 'double',
        'status' => ProductStatusEnum::class,
    ];

    public function slugUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->slug.'-'.$this->id,
        );
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function specifications()
    {
        return $this->hasMany(ProductSpecification::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(Adjustment::class, 'product_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
    }

    public function updater()
    {
        return $this->belongsTo(Admin::class, 'updated_by_id');
    }

    public function getHasVariantsAttribute(): bool
    {
        $variants = $this->variants;
        if ($variants->count() > 1) {
            return true;
        }
        if ($variants->isEmpty()) {
            return false;
        }

        $variant = $variants->first();
        if ($variant->relationLoaded('attributeValues')) {
            return $variant->attributeValues->isNotEmpty();
        }

        return $variant->attributeValues()->exists();
    }

    protected static function booted(): void
    {
        static::saved(function (Product $product) {
            static::clearProductCache($product->id);
        });

        static::deleted(function (Product $product) {
            static::clearProductCache($product->id);
        });
    }

    public static function clearProductCache(int $productId): void
    {
        \Illuminate\Support\Facades\Cache::forget("product_details_{$productId}");

        for ($i = 1; $i <= 10; $i++) {
            \Illuminate\Support\Facades\Cache::forget("popular_products_p{$i}_l20");
            \Illuminate\Support\Facades\Cache::forget("new_arrival_products_p{$i}_l20");
        }
    }
}

