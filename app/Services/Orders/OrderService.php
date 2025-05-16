<?php

namespace App\Services\Orders;

use Exception;
use App\Models\Cart;
use App\Models\User;
use App\Models\Orders\Order;
use App\Models\Orders\TaxRule;
use App\Models\Orders\OrderTax;
use App\Models\Orders\OrderItem;
use App\Models\Orders\VendorOrder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Orders\OrderCommission;


class OrderService
{

    public function createOrderFromCart(User $user, $sessionId = null)
    {
        return DB::transaction(function () use ($user, $sessionId) {
            $cartQuery = Cart::with(['product.vendor', 'variation'])
                ->where(function ($query) use ($user, $sessionId) {
                    $user ? $query->where('user_id', $user->id)
                        : $query->where('session_id', $sessionId);
                });

            $cartItems = $cartQuery->get();

            if ($cartItems->isEmpty()) {
                throw new Exception('Cart is empty', 400);
            }

            $groupedItems = $cartItems->groupBy('product.vendor_id');

            $order = Order::create([
                'user_id' => $user?->id,
                'order_number' => Order::generateOrderNumber(),
                'subtotal' => 0,
                'tax' => 0,
                'total_price' => 0,
                'currency_code'=>
                'status' => 'pending'
            ]);

            $mainSubtotal = 0;
            $mainTax = 0;
            $mainTotal = 0;

            foreach ($groupedItems as $vendorId => $items) {
                // Calculate vendor-specific amounts
                $vendorSubtotal = $items->sum(fn($item) => $item->price * $item->quantity);
                $taxDetails = $this->calculateVendorTaxes($vendorSubtotal);

                // Vendor total includes exclusive taxes only (inclusive already in subtotal)
                $vendorTotal = $vendorSubtotal + $taxDetails['exclusive_tax'];

                // Get vendor commission rate (default 10% if not set)
                $vendor = User::find($vendorId);
                $commissionRate = $vendor->commission_rate ?? 10.00;
                $commissionAmount = $vendorTotal * ($commissionRate / 100);

                // Create vendor order
                $vendorOrder = VendorOrder::create([
                    'order_id' => $order->id,
                    'vendor_id' => $vendorId,
                    'vendor_order_number' => VendorOrder::generateOrderNumber(),
                    'subtotal' => $vendorSubtotal,
                    'tax' => $taxDetails['total_tax'],
                    'commission_amount' => $commissionAmount,
                    'total_price' => $vendorTotal,
                    'status' => 'pending',
                    'fulfillment_type' => 'vendor',
                ]);

                foreach ($items as $cartItem) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'vendor_order_id' => $vendorOrder->id,
                        'product_id' => $cartItem->product_id,
                        'variation_id' => $cartItem->variation_id,
                        'quantity' => $cartItem->quantity,
                        'price' => $cartItem->price,
                    ]);
                }

                // Store tax breakdown
                foreach ($taxDetails['taxes'] as $tax) {
                    OrderTax::create([
                        'order_id' => $order->id,
                        'vendor_order_id' => $vendorOrder->id,
                        'tax_name' => $tax['name'],
                        'tax_rate' => $tax['rate'],
                        'tax_amount' => $tax['amount'],
                        'is_inclusive' => $tax['is_inclusive'],
                        'tax_type' => $tax['type'],
                        'tax_id' => $tax['id'],
                    ]);
                }

                // Record commission
                OrderCommission::create([
                    'vendor_order_id' => $vendorOrder->id,
                    'vendor_id' => $vendorId,
                    'amount' => $commissionAmount,
                    'rate' => $commissionRate,
                    'commission_type' => 'percentage',
                    'is_paid' => false,
                ]);

                // return OrderCommission::where('vendor_order_id', $vendorOrder->id)->get();
                // Update main order totals
                $mainSubtotal += $vendorSubtotal;
                $mainTax += $taxDetails['total_tax'];
                $mainTotal += $vendorTotal;
            }

            // Update main order with aggregated totals
            $order->update([
                'subtotal' => $mainSubtotal,
                'tax' => $mainTax,
                'total_price' => $mainTotal,
            ]);

            $cartQuery->delete();

            return $order->load(['vendorOrders', 'taxes', 'orderItems']);
        });
    }

    protected function calculateVendorTaxes(float $subtotal): array
    {
        $taxes = [];
        $exclusiveTax = 0;
        $inclusiveTax = 0;

        $activeRules = TaxRule::active()->get();

        foreach ($activeRules as $rule) {
            $taxAmount = $rule->is_inclusive
                ? $subtotal - ($subtotal / (1 + $rule->rate / 100))
                : ($subtotal * $rule->rate / 100);

            if ($rule->is_inclusive) {
                $inclusiveTax += $taxAmount;
            } else {
                $exclusiveTax += $taxAmount;
            }

            $taxes[] = [
                'name' => $rule->name,
                'rate' => $rule->rate,
                'amount' => round($taxAmount, 2),
                'is_inclusive' => $rule->is_inclusive,
                'type' => $rule->tax_type,
                'id' => $rule->tax_id,
            ];
        }

        return [
            'exclusive_tax' => $exclusiveTax,
            'inclusive_tax' => $inclusiveTax,
            'total_tax' => $exclusiveTax + $inclusiveTax,
            'taxes' => $taxes,
        ];
    }

    // Vendor Payment Handling
    // protected function createVendorPayment(
    //     Payment $parentPayment,
    //     VendorOrder $vendorOrder,
    //     float $totalAmount,
    //     float $commission
    // ): OrderPayment {
    //     return OrderPayment::create([
    //         'order_id' => $vendorOrder->order_id,
    //         'vendor_id' => $vendorOrder->vendor_id,
    //         'parent_payment_id' => $parentPayment->id,
    //         'method' => 'split_payment',
    //         'status' => 'settlement_pending',
    //         'amount' => $totalAmount,
    //         'vendor_amount' => $totalAmount - $commission,
    //         'platform_fee' => $commission,
    //         'currency_code' => $vendorOrder->currency_code,
    //         'is_split_payment' => true,
    //         'payment_type' => 'child',
    //         'split_details' => [
    //             'vendor_id' => $vendorOrder->vendor_id,
    //             'commission_rate' => $vendorOrder->commission_rate,
    //             'settlement_currency' => $vendorOrder->vendor->payment_currency
    //         ]
    //     ]);
    // }

    // Payment Transaction Recorder
    // protected function createPaymentTransaction(
    //     OrderPayment $payment,
    //     string $type,
    //     float $amount
    // ): PaymentTransaction {
    //     return PaymentTransaction::create([
    //         'payment_id' => $payment->id,
    //         'transaction_type' => $type,
    //         'amount' => $amount,
    //         'currency_code' => $payment->currency_code,
    //         'gateway_transaction_id' => Str::uuid(),
    //         'gateway_status' => 'pending_settlement',
    //         'is_success' => true,
    //         'metadata' => [
    //             'vendor_id' => $payment->vendor_id,
    //             'settlement_date' => now()->addDays(3)->toIso8601String()
    //         ]
    //     ]);
    // }
}