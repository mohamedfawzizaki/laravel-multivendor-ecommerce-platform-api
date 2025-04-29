<?php

namespace App\Observers;

use Illuminate\Support\Str;
use App\Models\Shipping\Shipment;
use Illuminate\Support\Facades\DB;
use App\Models\Orders\OrderPayment;

class OrderPaymentObserver
{
    public function updated(OrderPayment $payment)
    {
        if ($payment->isDirty('status') && $payment->status === 'paid') {
            $order = $payment->order;

            foreach ($order->vendorOrders as $vendorOrder) {
                // Only create a shipment if not already created
                if (!$vendorOrder->shipment) {
                    Shipment::create([
                        'user_id' => $order->user_id,
                        'vendor_id' => $vendorOrder->vendor_id,
                        'vendor_order_id' => $vendorOrder->id,
                        'carrier_id' => 1, // default carrier (or logic to select)
                        'shipping_address_id' => $order->shipping_address_id, // You may need to ensure relation
                        'tracking_number' => strtoupper(Str::random(10)),
                        'shipping_cost' => 0,
                        'insurance_cost' => 0,
                        'status' => 'label_created',
                        'label_created_at' => now(),
                    ]);
                }
            }
        }
    }
}