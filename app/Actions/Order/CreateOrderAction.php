<?php

namespace App\Actions\Order;

use App\Enums\OrderStatusEnum;
use App\Enums\StockMovementType;
use App\Enums\TransactionCategoryEnum;
use App\Enums\TransactionTypeEnum;
use App\Http\Requests\Admin\Order\StoreOrderRequest;
use App\Models\Account;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateOrderAction
{
    public function execute(StoreOrderRequest $request): Order
    {
        $userId = Auth::id();

        $items = $request->input('items');
        $accountId = $request->input('account_id');
        $discountAmount = (float) ($request->input('discount_amount') ?? 0);
        $taxRate = (float) ($request->input('tax_rate') ?? 0);
        $paidAmount = (float) $request->input('paid_amount');

        return DB::transaction(function () use ($request, $userId, $items, $accountId, $discountAmount, $taxRate, $paidAmount) {
            // Calculate totals
            $totalAmount = 0;
            $variantData = [];

            foreach ($items as $item) {
                $variant = ProductVariant::where('id', $item['product_variant_id'])

                    ->lockForUpdate()
                    ->firstOrFail();

                if ($variant->stock < $item['quantity']) {
                    throw new \RuntimeException("Insufficient stock for variant SKU: {$variant->sku}");
                }

                $lineTotal = (float) $item['unit_price'] * (int) $item['quantity'];
                $totalAmount += $lineTotal;

                $variantData[] = [
                    'variant' => $variant,
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => (float) $item['unit_price'],
                    'line_total' => $lineTotal,
                ];
            }

            $taxAmount = $taxRate > 0 ? ($totalAmount - $discountAmount) * ($taxRate / 100) : 0;
            $grandTotal = max(0, $totalAmount - $discountAmount + $taxAmount);
            $changeAmount = max(0, $paidAmount - $grandTotal);

            // Generate invoice no: INV{DDMMYYYY}-{NN}
            $today = now()->format('dmY');
            $todayOrderCount = Order::whereDate('created_at', today())->count();
            $invoiceNo = 'INV'.$today.'-'.str_pad($todayOrderCount + 1, 2, '0', STR_PAD_LEFT);

            // Build customer snapshot

            $clientId = $request->input('client_id');
            if ($paidAmount < $grandTotal && ! $clientId) {
                throw ValidationException::withMessages([
                    'paid_amount' => ['Walk-in customers cannot have a due balance.'],
                ]);
            }
            $clientSnapshot = null;
            if ($clientId) {
                $client = Client::where('id', $clientId)->firstOrFail();
                $clientSnapshot = [
                    'name' => $client->name,
                    'phone' => $client->phone,
                    'address' => $client->address,
                ];
            }

            // Create order
            $order = Order::create([
                'invoice_no' => $invoiceNo,
                'client_snapshot' => $clientSnapshot,
                'client_id' => $request->input('client_id', null),
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'status' => OrderStatusEnum::PLACED->value,
                'created_by_id' => $userId,
            ]);

            // Record initial status history
            $order->statusHistories()->create([
                'status' => OrderStatusEnum::PLACED,
                'note' => 'Order created.',
                'changed_by_id' => $userId,
            ]);

            // Create order items + reduce stock + record stock movement
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
                        'name' => $variant->product->name,
                        'sku' => $variant->sku,
                        'variant_title' => $variantTitle,
                    ],
                    'unit_price' => $vd['unit_price'],
                    'quantity' => $qty,
                    'total' => $vd['line_total'],
                ]);

                // Reduce stock
                $variant->decrement('stock', $qty);

                // Record stock movement
                StockMovement::create([
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'type' => StockMovementType::SALE,
                    'quantity' => $qty,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockBefore - $qty,
                    'reference_type' => OrderItem::class,
                    'reference_id' => $orderItem->id,
                    'reason' => "Sale order {$invoiceNo}",
                    'created_by_id' => $userId,
                ]);
            }

            // Record transaction + increment account balance
            if ($paidAmount > 0) {
                $account = Account::lockForUpdate()->findOrFail($accountId);

                Transaction::create([
                    'account_id' => $accountId,
                    'client_id' => $request->input('client_id', null),
                    'amount' => $paidAmount,
                    'type' => TransactionTypeEnum::IN,
                    'category' => TransactionCategoryEnum::SALE,
                    'reference_id' => $order->id,
                    'reference_type' => Order::class,
                    'description' => "Payment received for {$invoiceNo}",
                    'date' => now(),
                    'created_by_id' => $userId,
                ]);

                $account->increment('balance', $paidAmount);
            }

            return $order;
        });

        return $order;
    }
}
