<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    protected $fillable = [
        'name',
        'type',
        'values',
        'is_active',
        'is_default_specification',
    ];

    protected $casts = [
        'values' => 'array',
        'is_active' => 'boolean',
        'is_default_specification' => 'boolean',
    ];

    public function attributeValues()
    {
        return $this->hasMany(AttributeValue::class);
    }
}
