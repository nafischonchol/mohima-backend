<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Order\PlaceOrderRequest;
use App\Services\Customer\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * Display a listing of customer orders.
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $status = $request->query('status');

        return $this->orderService->getCustomerOrders($request->user(), $search, $status);
    }

    /**
     * Display the specified customer order detail.
     */
    public function show(Request $request, string|int $id): JsonResponse
    {
        return $this->orderService->getCustomerOrderDetail($request->user(), $id);
    }

    /**
     * Store a newly created order in storage.
     */
    public function store(PlaceOrderRequest $request): JsonResponse
    {
        return $this->orderService->placeOrder($request->validated(), $request->user());
    }
}
