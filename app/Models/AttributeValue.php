<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttributeValue extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'attribute_id',
        'value',
        'image',
        'meta_title',
        'meta_description',
        'is_active',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($value) {
            if ($value->attribute && $value->attribute->slug) {
                \Illuminate\Support\Facades\Cache::forget('customer_attribute_values_' . \Illuminate\Support\Str::slug($value->attribute->slug));
            }
        });

        static::deleted(function ($value) {
            if ($value->attribute && $value->attribute->slug) {
                \Illuminate\Support\Facades\Cache::forget('customer_attribute_values_' . \Illuminate\Support\Str::slug($value->attribute->slug));
            }
        });
    }

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
}
