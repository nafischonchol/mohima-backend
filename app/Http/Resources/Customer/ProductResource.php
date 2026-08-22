<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        $variants = $this->relationLoaded('variants') ? $this->variants : collect();

        $hasVariants = $variants->count() > 1;
        if ($variants->count() === 1) {
            $sku = $variants->first()->sku ?? '';
            if ($sku && !str_contains($sku, '-default-')) {
                $hasVariants = true;
            }
        }

        $minPrice = $variants->isNotEmpty() ? $variants->min('price') : 0;
        $maxPrice = $variants->isNotEmpty() ? $variants->max('price') : 0;
        $minDiscountPrice = $variants->isNotEmpty() ? $variants->min('discount_price') : 0;
        $maxDiscountPrice = $variants->isNotEmpty() ? $variants->max('discount_price') : 0;
        $totalStock = $variants->isNotEmpty() ? (int) $variants->sum('stock') : 0;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'slug_url' => $this->slug_url,
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ];
            }),
            'brand' => $this->whenLoaded('brand', function () {
                return [
                    'id' => $this->brand->id,
                    'name' => $this->brand->name,
                ];
            }),
            'thumbnail' => $this->image ? Storage::url($this->image) : null,
            'has_variants' => $hasVariants,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'min_discount_price' => $minDiscountPrice,
            'max_discount_price' => $maxDiscountPrice,
            'total_stock' => $totalStock,
            'status' => $this->status->value ?? $this->status,
            'rating' => 4.8,
            'reviews_count' => 10,
        ];
    }
}
