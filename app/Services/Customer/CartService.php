<?php

namespace App\Services\Customer;

use App\Http\Resources\Customer\CartResource;
use App\Models\Cart;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Auth;

class CartService
{
    public function getCartItems()
    {
        try {
            $clientId = Auth::id();

            $items = Cart::with(['product.brand', 'variant'])
                ->where('client_id', $clientId)
                ->latest('id')
                ->get();

            return responseSuccess(CartResource::collection($items), 'Cart items retrieved successfully');
        } catch (\Exception $e) {
            return responseError('Failed to retrieve cart items: ' . $e->getMessage(), 500, $e);
        }
    }

    public function addToCart(array $data)
    {
        try {
            $clientId = Auth::id();
            $variantId = $data['product_variant_id'] ?? null;

            // If variantId is null, pick the first active variant of the product
            if (!$variantId) {
                $firstActiveVariant = ProductVariant::where('product_id', $data['product_id'])
                    ->where('is_active', true)
                    ->first();
                $variantId = $firstActiveVariant?->id;
            }

            $cart = Cart::where('client_id', $clientId)
                ->where('product_id', $data['product_id'])
                ->where('product_variant_id', $variantId)
                ->first();

            if ($cart) {
                $cart->increment('quantity', $data['quantity']);
                $item = $cart->fresh(['product.brand', 'variant']);
            } else {
                $item = Cart::create([
                    'client_id' => $clientId,
                    'product_id' => $data['product_id'],
                    'product_variant_id' => $variantId,
                    'quantity' => $data['quantity'],
                ])->load(['product.brand', 'variant']);
            }

            return responseSuccess(CartResource::make($item), 'Item added to cart successfully');
        } catch (\Exception $e) {
            return responseError('Failed to add item to cart: ' . $e->getMessage(), 500, $e);
        }
    }

    public function updateCart(int $id, int $quantity)
    {
        try {
            $clientId = Auth::id();

            $cart = Cart::where('client_id', $clientId)->findOrFail($id);

            $cart->update(['quantity' => $quantity]);
            $item = $cart->fresh(['product.brand', 'variant']);

            return responseSuccess(CartResource::make($item), 'Cart updated successfully');
        } catch (\Exception $e) {
            return responseError('Failed to update cart: ' . $e->getMessage(), 500, $e);
        }
    }

    public function removeFromCart(int $id)
    {
        try {
            $clientId = Auth::id();

            $cart = Cart::where('client_id', $clientId)->findOrFail($id);

            $cart->delete();

            return responseSuccess(null, 'Item removed from cart successfully');
        } catch (\Exception $e) {
            return responseError('Failed to remove item from cart: ' . $e->getMessage(), 500, $e);
        }
    }

    public function clearCart()
    {
        try {
            $clientId = Auth::id();

            Cart::where('client_id', $clientId)->delete();

            return responseSuccess(null, 'Cart cleared successfully');
        } catch (\Exception $e) {
            return responseError('Failed to clear cart: ' . $e->getMessage(), 500, $e);
        }
    }
}
