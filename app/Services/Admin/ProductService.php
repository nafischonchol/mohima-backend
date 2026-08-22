<?php

namespace App\Services\Admin;

use App\Enums\ProductStatusEnum;
use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\ProductResource;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    public function index()
    {
        try {
            $products = Product::query()
                ->select(['id', 'name', 'slug', 'image', 'category_id', 'status'])
                ->with([
                    'category' => function ($query) {
                        $query->select(['id', 'name']);
                    },
                    'variants' => function ($query) {
                        $query->select(['id', 'product_id', 'sku', 'price', 'stock']);
                    },
                ])
                ->latest('id')
                ->get();

            return responseSuccess(ProductResource::collection($products));
        } catch (\Throwable $th) {
            return responseError('Failed to fetch products: ', 500, $th);
        }
    }

    public function show(Product $product)
    {
        try {
            // Load relationships for details
            $product->load([
                'category' => function ($query) {
                    $query->select(['id', 'name']);
                },
                'brand' => function ($query) {
                    $query->select(['id', 'name']);
                },
                'unit' => function ($query) {
                    $query->select(['id', 'name']);
                },
                'variants' => function ($query) {
                    $query->select(['id', 'product_id', 'sku', 'barcode', 'price', 'purchase_price', 'discount_price', 'stock']);
                },
            ]);

            return responseSuccess(new ProductDetailResource($product));
        } catch (\Throwable $th) {
            return responseError('Failed to fetch product details: ' . $th->getMessage(), 500);
        }
    }

    public function checkByBarcode(string $barcode)
    {
        try {
            // Find variant by barcode or SKU
            $variant = ProductVariant::where(function ($query) use ($barcode) {
                $query->where('barcode', $barcode)
                    ->orWhere('sku', $barcode);
            })->first();

            if (! $variant) {
                return responseError('Product not found with this barcode/SKU.', 404);
            }

            $product = $variant->product;

            // Check if product is active
            if ($product->status !== ProductStatusEnum::ACTIVE) {
                return responseError('This product is not active.', 400);
            }

            // Check if there is stock
            if ($variant->stock <= 0) {
                return responseError('Product is out of stock.', 400);
            }

            // Load relationships
            $product->load(['category', 'brand', 'unit']);

            // Format to match POSProduct structure on frontend
            $hasVariants = $product->has_variants;
            $id = $hasVariants ? 'var-' . $variant->id : (string) $product->id;
            $name = $hasVariants ? $product->name . ' - ' . $variant->sku : $product->name;

            $posProduct = [
                'id' => $id,
                'name' => $name,
                'banglaName' => $product->bangla_name,
                'sellPrice' => (float) $variant->price,
                'stock' => (int) $variant->stock,
                'barcode' => $variant->barcode ?? $variant->sku,
                'category' => $product->category->name ?? 'General',
                'image' => $product->image ? Storage::url($product->image) : null,
                'brand' => $product->brand->name ?? 'N/A',
                'sku' => $variant->sku,
                'variant_id' => $variant->id,
                'hasVariant' => false,
            ];

            return responseSuccess($posProduct, 'Product found successfully.');
        } catch (\Throwable $th) {
            return responseError('Failed to fetch product by barcode: ' . $th->getMessage(), 500, $th);
        }
    }

    public function stockMovements(Product $product)
    {
        try {
            $movements = StockMovement::where('product_id', $product->id)
                ->with([
                    'variant:id,sku,price',
                    'creator:id,name',
                    'reference' => function ($morphTo) {
                        $morphTo->morphWith([
                            OrderItem::class => ['order'],
                        ]);
                    },
                ])
                ->latest()
                ->paginate(50);

            return responseSuccess($movements, 'Stock movements retrieved successfully');
        } catch (\Throwable $th) {
            return responseError('Failed to fetch stock movements: ' . $th->getMessage(), 500, $th);
        }
    }
}
