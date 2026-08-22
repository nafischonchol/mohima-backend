<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductDetailsResource extends JsonResource
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

        $price = null;
        $discountPrice = null;
        if (!$hasVariants && $variants->isNotEmpty()) {
            $price = $variants->first()->price;
            $discountPrice = $variants->first()->discount_price;
        }

        $mappedVariants = $variants->map(function ($variant) {
            $attributes = $variant->relationLoaded('attributeValues') ? $variant->attributeValues->mapWithKeys(function ($av) {
                return [$av->attribute->name => $av->value];
            })->toArray() : [];

            return [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'price' => $variant->price,
                'discount_price' => $variant->discount_price,
                'stock' => $variant->stock,
                'attributes' => $attributes,
            ];
        });

        $aggregatedAttributes = [];
        if ($variants->isNotEmpty() && $variants->first()->relationLoaded('attributeValues')) {
            foreach ($variants as $variant) {
                foreach ($variant->attributeValues as $av) {
                    if ($av->relationLoaded('attribute')) {
                        $attrName = $av->attribute->name;
                        if (!isset($aggregatedAttributes[$attrName])) {
                            $aggregatedAttributes[$attrName] = [];
                        }
                        if (!in_array($av->value, $aggregatedAttributes[$attrName])) {
                            $aggregatedAttributes[$attrName][] = $av->value;
                        }
                    }
                }
            }
        }

        $formattedAttributes = [];
        foreach ($aggregatedAttributes as $name => $values) {
            $formattedAttributes[] = [
                'name' => $name,
                'values' => $values,
            ];
        }

        $keyIngredientValue = null;
        $specificationsFormatted = $this->whenLoaded('specifications', function () use (&$keyIngredientValue) {
            return $this->specifications->map(function ($spec) use (&$keyIngredientValue) {
                $attrName = $spec->attribute ? $spec->attribute->name : null;
                $val = $spec->value;
                if ($attrName && strcasecmp(trim($attrName), 'Key Ingredient') === 0) {
                    $keyIngredientValue = $val;
                }
                return [
                    'id' => $spec->id,
                    'attribute_id' => $spec->attribute_id,
                    'attribute_name' => $attrName,
                    'value' => $val,
                ];
            });
        });

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'slug_url' => $this->slug_url,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'ingredients' => $keyIngredientValue,
            'category' => $this->whenLoaded('category', function () {
                $breadcrumbs = [];
                if ($this->category) {
                    $ancestors = $this->category->relationLoaded('ancestors') ? $this->category->ancestors : collect();
                    foreach ($ancestors as $ancestor) {
                        $breadcrumbs[] = [
                            'id' => $ancestor->id,
                            'name' => $ancestor->name,
                            'slug' => $ancestor->slug,
                            'slug_url' => $ancestor->slug_url,
                        ];
                    }
                    $breadcrumbs[] = [
                        'id' => $this->category->id,
                        'name' => $this->category->name,
                        'slug' => $this->category->slug,
                        'slug_url' => $this->category->slug_url,
                    ];
                }

                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                    'slug_url' => $this->category->slug_url,
                    'breadcrumbs' => $breadcrumbs,
                ];
            }),
            'brand' => $this->whenLoaded('brand', function () {
                return [
                    'id' => $this->brand->id,
                    'name' => $this->brand->name,
                ];
            }),
            'thumbnail' => !empty($this->image) ? Storage::url($this->image) : null,
            'images' => $this->whenLoaded('images', function () {
                return $this->images
                    ->filter(fn($img) => !empty($img->image_path))
                    ->map(fn($img) => Storage::url($img->image_path))
                    ->values();
            }),
            'specifications' => $specificationsFormatted,
            'has_variants' => $hasVariants,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'min_discount_price' => $minDiscountPrice,
            'max_discount_price' => $maxDiscountPrice,
            'price' => $price ?? $minPrice,
            'discount_price' => $discountPrice ?? $minDiscountPrice,
            'status' => $this->status->value ?? $this->status,
            'rating' => 4.8,
            'reviews_count' => 10,
            'variants' => $mappedVariants,
            'attributes' => $formattedAttributes,
        ];
    }
}
