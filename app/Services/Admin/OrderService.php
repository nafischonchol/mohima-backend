<?php

namespace App\Services\Admin;

use App\Enums\OrderStatusEnum;
use App\Http\Resources\OrderListResource;
use App\Http\Resources\OrderLookupResource;
use App\Models\Order;

class OrderService
{
    public function lookup(?string $search = null)
    {
        try {
            $query = Order::query()
                ->with(['client:id,name,phone'])
                ->latest('id');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_no', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($cq) use ($search) {
                            $cq->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            }

            $orders = $query->get();

            return responseSuccess(OrderLookupResource::collection($orders));
        } catch (\Throwable $th) {
            return responseError('Failed to fetch order lookup: '.$th->getMessage(), 500);
        }
    }

    public function index(?int $clientId = null)
    {
        try {
            $orders = Order::query()
                ->when($clientId, fn ($q) => $q->where('client_id', $clientId))
                ->with([
                    'items' => function ($query) {
                        $query->select(['id', 'order_id', 'quantity', 'unit_price', 'total']);
                    },
                    'createdBy' => function ($query) {
                        $query->select(['id', 'name']);
                    },
                    'client' => function ($query) {
                        $query->select(['id', 'name', 'phone']);
                    },
                    'parcels',
                ])
                ->latest('id')
                ->get();

            return responseSuccess(OrderListResource::collection($orders));
        } catch (\Throwable $th) {
            return responseError('Failed to fetch orders: '.$th->getMessage(), 500);
        }
    }

    public function show(Order $order)
    {
        try {
            $order->load([
                'items' => function ($query) {
                    $query->select(['id', 'order_id', 'product_id', 'product_variant_id', 'product_snapshot', 'unit_price', 'quantity', 'total']);
                },
                'transactions.account',
                'client',
                'createdBy' => function ($query) {
                    $query->select(['id', 'name']);
                },
                'statusHistories.changedBy' => function ($query) {
                    $query->select(['id', 'name']);
                },
            ]);

            return responseSuccess([
                'items' => [
                    'id' => $order->id,
                    'invoice_no' => $order->invoice_no,
                    'date' => $order->created_at->toISOString(),
                    'status' => $order->status,
                    'parcel_booking_status' => $order->parcel_booking_status ?? 'none',
                    'latest_courier_provider' => $order->latest_courier_provider,
                    'parcels' => $order->parcels,
                    'client_id' => $order->client_id,
                    'customer_name' => $order->client_snapshot['name'] ?? $order->client?->name ?? 'Walk-in Customer',
                    'customer_phone' => $order->client_snapshot['phone'] ?? $order->client?->phone ?? null,
                    'customer_address' => $order->client_snapshot['address'] ?? $order->client?->address ?? null,
                    'customer_city' => $order->client_snapshot['city'] ?? $order->client?->city ?? null,
                    'client_snapshot' => $order->client_snapshot,
                    'items' => $order->items->map(fn ($item) => [
                        'id' => (string) $item->id,
                        'name' => $item->product_snapshot['name'] ?? '',
                        'product_snapshot' => $item->product_snapshot,
                        'unit_price' => (float) $item->unit_price,
                        'quantity' => $item->quantity,
                        'total' => (float) $item->total,
                    ]),
                    'status_histories' => $order->statusHistories->map(fn ($history) => [
                        'id' => $history->id,
                        'status' => $history->status?->value ?? (string) $history->status,
                        'note' => $history->note,
                        'changed_by' => $history->changedBy?->name ?? 'System',
                        'created_at' => $history->created_at->toISOString(),
                    ]),
                    'subtotal' => (float) $order->total_amount,
                    'discount_amount' => (float) $order->discount_amount,
                    'tax_amount' => (float) $order->tax_amount,
                    'delivery_charge' => $order->delivery_charge !== null ? (float) $order->delivery_charge : null,
                    'grand_total' => (float) $order->grand_total,
                    'paid_amount' => (float) $order->paid_amount,
                    'change_amount' => (float) $order->change_amount,
                    'payment_method' => $order->transactions->map(fn ($t) => $t->account?->name)->filter()->join(', ') ?: 'N/A',
                    'created_by' => $order->createdBy?->name ?? 'N/A',
                    'created_at' => $order->created_at->toISOString(),
                ],
            ]);
        } catch (\Throwable $th) {
            return responseError('Failed to fetch order details: '.$th->getMessage(), 500);
        }
    }

    public function update(Order $order, array $data)
    {
        try {
            $snapshot = $order->client_snapshot ?? [];

            if (array_key_exists('customer_name', $data)) {
                $snapshot['name'] = $data['customer_name'];
            }
            if (array_key_exists('customer_phone', $data)) {
                $snapshot['phone'] = $data['customer_phone'];
            }
            if (array_key_exists('customer_address', $data)) {
                $snapshot['address'] = $data['customer_address'];
            }
            if (array_key_exists('customer_city', $data)) {
                $snapshot['city'] = $data['customer_city'];
            }

            $order->client_snapshot = $snapshot;

            if (array_key_exists('delivery_charge', $data)) {
                $deliveryCharge = $data['delivery_charge'] !== null ? (float) $data['delivery_charge'] : null;
                $order->delivery_charge = $deliveryCharge;
                $order->grand_total = (float) $order->total_amount - (float) $order->discount_amount + (float) $order->tax_amount + ($deliveryCharge ?? 0);
            }

            $order->save();

            return $this->show($order);
        } catch (\Throwable $th) {
            return responseError('Failed to update order: '.$th->getMessage(), 500);
        }
    }
}
