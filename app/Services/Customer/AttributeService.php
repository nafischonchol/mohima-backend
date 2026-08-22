<?php

namespace App\Services\Customer;

use App\Models\Attribute;
use App\Http\Resources\AttributeResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AttributeService
{
    public function getAttributeValues(string $slug, Request $request)
    {
        $formattedSlug = \Illuminate\Support\Str::slug($slug);
        $cacheKey = 'customer_attribute_values_' . $formattedSlug;

        if (Cache::has($cacheKey)) {
            $attribute = Cache::get($cacheKey);
        } else {
            $attribute = Attribute::with(['attributeValues' => function ($query) {
                $query->active();
            }])
                ->where('slug', $formattedSlug)
                ->active()
                ->first();

            if ($attribute) {
                Cache::put($cacheKey, $attribute, now()->addHours(10));
            }
        }

        if (!$attribute) {
            return responseError("Attribute '{$slug}' not found.", 404);
        }

        return responseSuccess(AttributeResource::make($attribute));
    }
}
