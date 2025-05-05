<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessVendorOrders implements ShouldQueue
{
    public function handle()
    {
        // $this->order->vendorOrders->each(function ($vendorOrder) {
        //     // Calculate commission
        //     $commission = $vendorOrder->subtotal * $vendorOrder->vendor->commission_rate;

        //     OrderCommission::create([
        //         'vendor_order_id' => $vendorOrder->id,
        //         'vendor_id' => $vendorOrder->vendor_id,
        //         'amount' => $commission,
        //         'rate' => $vendorOrder->vendor->commission_rate,
        //         'type' => 'percentage'
        //     ]);

        //     // Notify vendor
        //     $vendorOrder->vendor->notify(new NewOrderNotification($vendorOrder));

        //     // Initiate shipping
        //     if ($vendorOrder->fulfillment_type === 'vendor') {
        //         CreateVendorShipment::dispatch($vendorOrder);
        //     }
        // });
    }
}