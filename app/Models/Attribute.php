<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'type',
        'values',
        'is_active',
        'is_default_specification',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($attribute) {
            if (empty($attribute->slug) || $attribute->isDirty('name')) {
                $attribute->slug = \Illuminate\Support\Str::slug($attribute->name);
            }
        });

        static::saved(function ($attribute) {
            if ($attribute->slug) {
                \Illuminate\Support\Facades\Cache::forget('customer_attribute_values_' . \Illuminate\Support\Str::slug($attribute->slug));
            }
        });

        static::deleted(function ($attribute) {
            if ($attribute->slug) {
                \Illuminate\Support\Facades\Cache::forget('customer_attribute_values_' . \Illuminate\Support\Str::slug($attribute->slug));
            }
        });
    }

    protected $casts = [
        'values' => 'array',
        'is_active' => 'boolean',
        'is_default_specification' => 'boolean',
    ];


    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function attributeValues()
    {
        return $this->hasMany(AttributeValue::class);
    }
}
