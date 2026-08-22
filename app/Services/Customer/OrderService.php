<?php

namespace App\Services\Customer;

use App\Enums\OrderStatusEnum;
use App\Enums\StockMovementType;
use App\Models\Cart;
use App\Models\ClientAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Place an order for customer checkout using cart IDs.
     *
     * @return JsonResponse
     */
    public function placeOrder(array $validated, Authenticatable $client)
    {
        try {
            $order = DB::transaction(function () use ($validated, $client) {
                $cartIds = $validated['cart_ids'];

                $cartItems = Cart::where('client_id', $client->id)
                    ->whereIn('id', $cartIds)
                    ->with(['variant', 'product'])
                    ->get();

                if ($cartItems->count() === 0) {
                    throw new \RuntimeException('No valid cart items found for the order.');
                }

                $totalAmount = 0;
                $variantData = [];

                foreach ($cartItems as $cartItem) {
                    $variant = ProductVariant::where('id', $cartItem->product_variant_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($variant->stock < $cartItem->quantity) {
                        throw new \RuntimeException("Insufficient stock for product SKU: {$variant->sku}");
                    }

                    $unitPrice = (float) ($variant->price ?? 0);
                    $lineTotal = $unitPrice * (int) $cartItem->quantity;
                    $totalAmount += $lineTotal;

                    $variantData[] = [
                        'variant' => $variant,
                        'quantity' => (int) $cartItem->quantity,
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                    ];
                }

                $discountAmount = 0;
                $taxAmount = 0;
                $grandTotal = $totalAmount;

                $today = now()->format('dmY');
                $todayOrderCount = Order::whereDate('created_at', today())->count();
                $invoiceNo = 'INV'.$today.'-'.str_pad($todayOrderCount + 1, 2, '0', STR_PAD_LEFT);

                if (! empty($validated['address_id'])) {
                    $clientAddress = ClientAddress::where('client_id', $client->id)
                        ->where('id', $validated['address_id'])
                        ->firstOrFail();

                    $clientSnapshot = [
                        'name' => $clientAddress->name,
                        'phone' => $clientAddress->phone,
                        'address' => $clientAddress->address,
                        'city' => $clientAddress->city,
                        'company_name' => $clientAddress->company_name,
                        'label' => $clientAddress->label,
                    ];
                } else {
                    $clientSnapshot = [
                        'name' => $validated['name'] ?? $client->name,
                        'phone' => $validated['phone'] ?? $client->phone,
                        'address' => $validated['address'] ?? '',
                        'city' => $validated['city'] ?? '',
                        'company_name' => $validated['company_name'] ?? null,
                        'label' => 'HOME',
                    ];
                }

                $order = Order::create([
                    'invoice_no' => $invoiceNo,
                    'client_id' => $client->id,
                    'client_snapshot' => $clientSnapshot,
                    'total_amount' => $totalAmount,
                    'discount_amount' => $discountAmount,
                    'tax_amount' => $taxAmount,
                    'grand_total' => $grandTotal,
                    'paid_amount' => 0,
                    'change_amount' => 0,
                    'status' => OrderStatusEnum::PLACED->value,
                    'created_by_id' => null,
                ]);

                $order->statusHistories()->create([
                    'status' => OrderStatusEnum::PLACED,
                    'note' => 'Order placed by customer via online checkout.',
                    'changed_by_id' => null,
                ]);

                foreach ($variantData as $vd) {
                    $variant = $vd['variant'];
                    $qty = $vd['quantity'];
                    $stockBefore = $variant->stock;

                    $variantTitle = $variant->attributeValues->isNotEmpty()
                        ? $variant->attributeValues->pluck('value')->join(' / ')
                        : 'Default';

                    $orderItem = OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $variant->product_id,
                        'product_variant_id' => $variant->id,
                        'product_snapshot' => [
                            'name' => $variant->product->name ?? '',
                            'sku' => $variant->sku,
                            'variant_title' => $variantTitle,
                        ],
                        'unit_price' => $vd['unit_price'],
                        'quantity' => $qty,
                        'total' => $vd['line_total'],
                    ]);

                    $variant->decrement('stock', $qty);

                    StockMovement::create([
                        'product_id' => $variant->product_id,
                        'product_variant_id' => $variant->id,
                        'type' => StockMovementType::SALE,
                        'quantity' => $qty,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockBefore - $qty,
                        'reference_type' => OrderItem::class,
                        'reference_id' => $orderItem->id,
                        'reason' => "Online Sale Order {$invoiceNo}",
                        'created_by_id' => null,
                    ]);
                }

                // Delete cart items that were placed in this order
                Cart::where('client_id', $client->id)
                    ->whereIn('id', $cartIds)
                    ->delete();

                return $order;
            });

            return responseSuccess([
                'order_id' => $order->id,
                'invoice_no' => $order->invoice_no,
                'grand_total' => (float) $order->grand_total,
                'status' => $order->status->value,
            ], 'Order placed successfully');
        } catch (\Throwable $th) {
            return responseError('Failed to place order: '.$th->getMessage(), 422);
        }
    }

    /**
     * Get customer order list.
     *
     * @return JsonResponse
     */
    public function getCustomerOrders(Authenticatable $client, ?string $search = null, ?string $status = null)
    {
        try {
            $query = Order::where('client_id', $client->id)
                ->withCount('items')
                ->with(['items' => function ($q) {
                    $q->select(['id', 'order_id', 'product_id', 'product_variant_id', 'product_snapshot', 'unit_price', 'quantity', 'total']);
                }])
                ->latest('id');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_no', 'like', "%{$search}%")
                        ->orWhereHas('items', function ($iq) use ($search) {
                            $iq->where('product_snapshot->name', 'like', "%{$search}%");
                        });
                });
            }

            if ($status && $status !== 'all') {
                $query->where('status', $status);
            }

            $orders = $query->get();

            $formattedOrders = $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'invoice_no' => $order->invoice_no,
                    'date' => $order->created_at->format('M d, Y'),
                    'created_at' => $order->created_at->toISOString(),
                    'status' => is_object($order->status) ? $order->status->value : (string) $order->status,
                    'items_count' => $order->items_count,
                    'total_amount' => (float) $order->total_amount,
                    'discount_amount' => (float) $order->discount_amount,
                    'tax_amount' => (float) $order->tax_amount,
                    'delivery_charge' => $order->delivery_charge !== null ? (float) $order->delivery_charge : null,
                    'grand_total' => (float) $order->grand_total,
                    'items' => $order->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'name' => $item->product_snapshot['name'] ?? '',
                            'sku' => $item->product_snapshot['sku'] ?? '',
                            'variant_title' => $item->product_snapshot['variant_title'] ?? '',
                            'unit_price' => (float) $item->unit_price,
                            'quantity' => $item->quantity,
                            'total' => (float) $item->total,
                        ];
                    }),
                ];
            });

            return responseSuccess($formattedOrders);
        } catch (\Throwable $th) {
            return responseError('Failed to fetch orders: '.$th->getMessage(), 500);
        }
    }

    /**
     * Get customer order detail.
     *
     * @return JsonResponse
     */
    public function getCustomerOrderDetail(Authenticatable $client, int|string $id)
    {
        try {
            $query = Order::where('client_id', $client->id);

            if (is_numeric($id)) {
                $query->where('id', $id);
            } else {
                $query->where('invoice_no', $id);
            }

            $order = $query->with([
                'items',
                'statusHistories' => function ($q) {
                    $q->latest('id');
                },
            ])->first();

            if (! $order) {
                return responseError('Order not found', 404);
            }

            $formattedOrder = [
                'id' => $order->id,
                'invoice_no' => $order->invoice_no,
                'date' => $order->created_at->format('M d, Y h:i A'),
                'created_at' => $order->created_at->toISOString(),
                'status' => is_object($order->status) ? $order->status->value : (string) $order->status,
                'client_snapshot' => $order->client_snapshot,
                'total_amount' => (float) $order->total_amount,
                'discount_amount' => (float) $order->discount_amount,
                'tax_amount' => (float) $order->tax_amount,
                'delivery_charge' => $order->delivery_charge !== null ? (float) $order->delivery_charge : null,
                'grand_total' => (float) $order->grand_total,
                'paid_amount' => (float) $order->paid_amount,
                'items' => $order->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->product_snapshot['name'] ?? '',
                        'sku' => $item->product_snapshot['sku'] ?? '',
                        'variant_title' => $item->product_snapshot['variant_title'] ?? '',
                        'unit_price' => (float) $item->unit_price,
                        'quantity' => $item->quantity,
                        'total' => (float) $item->total,
                    ];
                }),
                'status_histories' => $order->statusHistories->map(function ($history) {
                    return [
                        'id' => $history->id,
                        'status' => is_object($history->status) ? $history->status->value : (string) $history->status,
                        'note' => $history->note,
                        'created_at' => $history->created_at->format('M d, Y h:i A'),
                    ];
                }),
            ];

            return responseSuccess([
                'items' => $formattedOrder,
            ]);
        } catch (\Throwable $th) {
            return responseError('Failed to fetch order detail: '.$th->getMessage(), 500);
        }
    }
}

