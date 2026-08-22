<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Cart\AddToCartRequest;
use App\Http\Requests\Customer\Cart\UpdateCartRequest;
use App\Services\Customer\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function index()
    {
        return $this->cartService->getCartItems();
    }

    public function store(AddToCartRequest $request)
    {
        return $this->cartService->addToCart($request->validated());
    }

    public function update(UpdateCartRequest $request, $id)
    {
        return $this->cartService->updateCart($id, $request->validated()['quantity']);
    }

    public function destroy($id)
    {
        return $this->cartService->removeFromCart($id);
    }

    public function clear()
    {
        return $this->cartService->clearCart();
    }
}
