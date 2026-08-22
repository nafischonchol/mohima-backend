<?php

namespace App\Services\Customer;

use App\Enums\OrderStatusEnum;
use App\Http\Requests\Customer\ProductFilterRequest;
use App\Http\Resources\Customer\ProductDetailsResource;
use App\Http\Resources\Customer\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    private function getPaginationParams(Request $request): array
    {
        $perPage = (int) $request->input('per_page', 20);
        if ($perPage > 100) {
            $perPage = 100;
        }

        $page = (int) $request->input('page', 1);
        if ($page > 50) {
            $page = 50;
        }

        if ($page < 1) $page = 1;
        if ($perPage < 1) $perPage = 1;

        return [$page, $perPage];
    }

    public function popularProducts(Request $request)
    {
        [$page, $perPage] = $this->getPaginationParams($request);
        $cacheKey = "popular_products_p{$page}_l{$perPage}";

        if (Cache::has($cacheKey)) {
            $response = Cache::get($cacheKey);
        } else {
            $products = Product::where('status', 'active')
                ->with(['variants', 'category', 'brand'])
                ->withSum(['orderItems as total_sales' => function ($query) {
                    $query->whereHas('order', function ($q) {
                        $q->where('created_at', '>=', now()->subDays(40))
                            ->whereNotIn('status', [
                                OrderStatusEnum::CANCELLED->value,
                                OrderStatusEnum::RETURNED->value,
                            ]);
                    });
                }], 'quantity')
                ->orderByDesc('total_sales')
                ->orderByDesc('id')
                ->paginate($perPage, ['*'], 'page', $page);

            $response = [
                'items' => ProductResource::collection($products),
                'pagination' => pagination($products)
            ];

            Cache::put($cacheKey, $response, now()->addHours(2));
        }

        return responseSuccess($response);
    }

    public function newArrivalProducts(Request $request)
    {
        [$page, $perPage] = $this->getPaginationParams($request);
        $cacheKey = "new_arrival_products_p{$page}_l{$perPage}";

        if (Cache::has($cacheKey)) {
            $response = Cache::get($cacheKey);
        } else {
            $products = Product::where('status', 'active')
                ->with(['variants', 'category', 'brand'])
                ->orderByDesc('id')
                ->paginate($perPage, ['*'], 'page', $page);

            $response = [
                'items' => ProductResource::collection($products),
                'pagination' => pagination($products)
            ];

            Cache::put($cacheKey, $response, now()->addHours(2));
        }

        return responseSuccess($response);
    }

    public function show($slug_url)
    {
        $id = extractIdFromSlug($slug_url);
        $cacheKey = "product_details_{$id}";

        if (Cache::has($cacheKey)) {
            $product = Cache::get($cacheKey);
        } else {
            $product = Product::where('status', 'active')
                ->with([
                    'category' => function ($query) {
                        $query->with('ancestors');
                    },
                    'brand',
                    'specifications.attribute',
                    'variants.attributeValues.attribute',
                    'images'
                ])
                ->findOrFail($id);

            Cache::put($cacheKey, $product, now()->addHours(2));
        }

        return responseSuccess(ProductDetailsResource::make($product));
    }

    public function brandProducts(Request $request, $brandId)
    {
        [$page, $perPage] = $this->getPaginationParams($request);

        $products = Product::where('status', 'active')
            ->where('brand_id', $brandId)
            ->with(['variants', 'category', 'brand'])
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return responseSuccess([
            'items' => ProductResource::collection($products),
            'pagination' => pagination($products)
        ]);
    }

    public function categoryProducts(Request $request, $categoryId)
    {
        [$page, $perPage] = $this->getPaginationParams($request);

        $products = Product::where('status', 'active')
            ->where('category_id', $categoryId)
            ->with(['variants', 'category', 'brand'])
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return responseSuccess([
            'items' => ProductResource::collection($products),
            'pagination' => pagination($products)
        ]);
    }

    public function filterProducts(ProductFilterRequest $request)
    {
        [$page, $perPage] = $this->getPaginationParams($request);

        $query = Product::where('status', 'active')
            ->with(['variants', 'category', 'brand']);

        if ($request->filled('search_text')) {
            $searchText = $request->input('search_text');
            $query->where(function ($q) use ($searchText) {
                $q->where('name', 'like', "%{$searchText}%")
                    ->orWhere('bangla_name', 'like', "%{$searchText}%")
                    ->orWhereHas('category', function ($catQ) use ($searchText) {
                        $catQ->where('name', 'like', "%{$searchText}%");
                    })
                    ->orWhereHas('brand', function ($brandQ) use ($searchText) {
                        $brandQ->where('name', 'like', "%{$searchText}%");
                    });
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('sub_category_id')) {
            $query->where('category_id', $request->input('sub_category_id'));
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->input('brand_id'));
        }

        if ($request->filled('is_stock')) {
            $isStock = filter_var($request->input('is_stock'), FILTER_VALIDATE_BOOLEAN);
            if ($isStock) {
                $query->whereHas('variants', function ($q) {
                    $q->where('stock', '>', 0);
                });
            }
        }

        if ($request->input('sort_by') === 'best_selling') {
            $query->withSum(['orderItems as total_sales' => function ($q) {
                $q->whereHas('order', function ($ordQ) {
                    $ordQ->where('created_at', '>=', now()->subDays(40))
                        ->whereNotIn('status', [
                            OrderStatusEnum::CANCELLED->value,
                            OrderStatusEnum::RETURNED->value,
                        ]);
                });
            }], 'quantity')
                ->orderByDesc('total_sales')
                ->orderByDesc('id');
        } elseif ($request->input('sort_by') === 'price_low_to_high') {
            $query->addSelect([
                'min_variant_price' => ProductVariant::select('price')
                    ->whereColumn('product_id', 'products.id')
                    ->active()
                    ->orderBy('price', 'asc')
                    ->limit(1)
            ])->orderBy('min_variant_price', 'asc')->orderByDesc('id');
        } elseif ($request->input('sort_by') === 'price_high_to_low') {
            $query->addSelect([
                'min_variant_price' => ProductVariant::select('price')
                    ->whereColumn('product_id', 'products.id')
                    ->active()
                    ->orderBy('price', 'asc')
                    ->limit(1)
            ])->orderBy('min_variant_price', 'desc')->orderByDesc('id');
        } else {
            $query->orderByDesc('id');
        }

        $products = $query->paginate($perPage, ['*'], 'page', $page);

        return responseSuccess([
            'items' => ProductResource::collection($products),
            'pagination' => pagination($products)
        ]);
    }
}

