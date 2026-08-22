<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $product = $this->product;
        $variant = $this->variant;

        $rawPrice = $variant?->discount_price ?? $variant?->price ?? 0;
        $imagePath = $variant?->image ?? $product?->image ?? null;
        $imageUrl = $imagePath ? Storage::url($imagePath) : null;

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'name' => $product?->name ?? '',
            'price' => (float) $rawPrice,
            'image' => $imageUrl,
            'quantity' => (int) $this->quantity,
            'brand' => $product?->brand?->name ?? null,
            'sku' => $variant?->sku ?? null,
            'slug_url' => $product?->slug_url ?? null,
        ];
    }
}
