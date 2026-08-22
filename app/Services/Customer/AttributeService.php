<?php

namespace App\Services\Customer;

use App\Models\Attribute;
use App\Http\Resources\AttributeResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AttributeService
{
    public function getAttributeValues(string $name, Request $request)
    {
        $cacheKey = 'customer_attribute_values_' . strtolower($name);

        if (Cache::has($cacheKey)) {
            $attribute = Cache::get($cacheKey);
        } else {
            $attribute = Attribute::with(['attributeValues' => function ($query) {
                $query->where('is_active', true);
            }])
                ->where('name', $name)
                ->where('is_active', true)
                ->first();

            if ($attribute) {
                Cache::put($cacheKey, $attribute, now()->addHours(10));
            }
        }

        if (!$attribute) {
            return responseError("Attribute '{$name}' not found.", 404);
        }

        return responseSuccess(AttributeResource::make($attribute));
    }
}
