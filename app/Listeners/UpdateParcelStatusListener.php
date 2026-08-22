<?php

namespace App\Listeners;

use App\Models\OrderParcel;
use Bonik\Courier\Events\CourierWebhookReceived;

class UpdateParcelStatusListener
{
    public function handle(CourierWebhookReceived $event): void
    {
        $providerKey = strtolower($event->provider);
        $data = $event->webhookData;

        $trackingCode = $data['tracking_code'] ?? null;
        $consignmentId = $data['consignment_id'] ?? null;
        $invoiceId = $data['invoice_id'] ?? null;
        $normalizedStatus = $data['status'] ?? 'pending';

        // Find order_parcel entry matching tracking_code, consignment_id, or invoice_id
        $query = OrderParcel::where('courier_provider', $providerKey);

        if ($trackingCode) {
            $query->where('tracking_code', $trackingCode);
        } elseif ($consignmentId) {
            $query->where('consignment_id', $consignmentId);
        } elseif ($invoiceId) {
            $query->where('invoice_id', $invoiceId);
        } else {
            return;
        }

        $parcel = $query->latest('id')->first();

        if ($parcel) {
            $parcel->update([
                'status' => $normalizedStatus,
                'raw_status' => $data['raw_status'] ?? $parcel->raw_status,
                'response_payload' => $data['history_entry'] ?? $data,
            ]);
        }
    }
}
