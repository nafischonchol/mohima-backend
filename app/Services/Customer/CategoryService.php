<?php

namespace App\Services\Customer;

use App\Models\Category;
use App\Http\Resources\CategoryResource;
use Illuminate\Support\Facades\Cache;

class CategoryService
{
    public function popularCategories()
    {
        $cacheKey = 'popular_categories';

        if (Cache::has($cacheKey)) {
            $categories = Cache::get($cacheKey);
        } else {
            $categories = Category::where('is_active', true)
                ->inRandomOrder()
                ->select("id", "name", "slug", "icon")
                ->take(20)
                ->get();

            Cache::put($cacheKey, $categories, now()->addHours(10));
        }

        return responseSuccess(CategoryResource::collection($categories));
    }
}

