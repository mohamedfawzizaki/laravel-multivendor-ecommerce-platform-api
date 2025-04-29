<?php

namespace App\Services\Orders;

use Exception;
use App\Models\User;
use App\Models\Orders\Order;
use Illuminate\Support\Facades\DB;
use App\Models\Orders\OrderPayment;
use App\Exceptions\PaymentProcessingException;

class PaymentService
{
    public function processOrderPayment(Order $order, string $paymentMethod, array $paymentData): OrderPayment
    {
        return DB::transaction(function () use ($order, $paymentMethod, $paymentData) {
            if ($order->is_multi_vendor) {
                return $this->processMultiVendorPayment($order, $paymentMethod, $paymentData);
            }

            return $this->processSingleVendorPayment($order, $paymentMethod, $paymentData);
        });
    }

    protected function processMultiVendorPayment(Order $order, string $paymentMethod, array $paymentData): OrderPayment
    {
        $parentPayment = $this->createParentPayment($order, $paymentMethod, $paymentData);

        foreach ($order->vendors as $vendor) {
            $this->createVendorPayment($parentPayment, $vendor, $order);
        }

        $this->processParentPayment($parentPayment, $paymentData);

        return $parentPayment;
    }

    protected function createParentPayment(Order $order, string $method, array $data): OrderPayment
    {
        return OrderPayment::create([
            'order_id' => $order->id,
            'method' => $method,
            'payment_type' => OrderPayment::TYPE_PARENT,
            'status' => OrderPayment::STATUS_SPLIT_PENDING,
            'amount' => $order->total,
            'currency_code' => $order->currency_code,
            'is_split_payment' => true,
            'split_details' => [
                'total_vendors' => $order->vendors->count(),
                'total_amount' => $order->total,
                'currency' => $order->currency_code
            ],
            'gateway_response' => $data['gateway_response'] ?? null
        ]);
    }

    protected function createVendorPayment(OrderPayment $parentPayment, User $vendor, Order $order): OrderPayment
    {
        $vendorTotal = $order->getVendorTotal($vendor->id);

        return $parentPayment->createChildPayment([
            'order_id' => $order->id,
            'vendor_id' => $vendor->id,
            'method' => $this->getVendorPaymentMethod($vendor, $parentPayment->method),
            'amount' => $vendorTotal,
            'currency_code' => $order->currency_code,
            'vendor_amount' => $this->calculateVendorAmount($vendorTotal, $vendor),
            'platform_fee' => $this->calculatePlatformFee($vendorTotal, $vendor),
            'gateway_response' => null
        ]);
    }

    protected function processParentPayment(OrderPayment $parentPayment, array $data): void
    {
        try {
            $this->processPaymentMethod($parentPayment, $data);
            
            if ($parentPayment->status === OrderPayment::STATUS_PAID) {
                $parentPayment->childPayments->each->markAsPaid();
            }
        } catch (Exception $e) {//(PaymentProcessingException $e) {
            $parentPayment->update(['status' => OrderPayment::STATUS_FAILED]);
            $parentPayment->childPayments->each->update(['status' => OrderPayment::STATUS_FAILED]);
            throw $e;
        }
    }

    protected function processSingleVendorPayment(Order $order, string $paymentMethod, array $paymentData): OrderPayment
    {
        $payment = OrderPayment::create([
            'order_id' => $order->id,
            'vendor_id' => $order->vendor_id,
            'payment_type' => OrderPayment::TYPE_STANDALONE,
            'method' => $paymentMethod,
            'status' => OrderPayment::STATUS_PENDING,
            'amount' => $order->total,
            'vendor_amount' => $this->calculateVendorAmount($order->total, $order->vendor),
            'platform_fee' => $this->calculatePlatformFee($order->total, $order->vendor),
            'currency_code' => $order->currency_code,
            'gateway_response' => $paymentData['gateway_response'] ?? null
        ]);

        $this->processPaymentMethod($payment, $paymentData);

        return $payment;
    }

    protected function processPaymentMethod(OrderPayment $payment, array $data): void
    {
        try {
            switch ($payment->method) {
                case OrderPayment::METHOD_STRIPE:
                    $this->processStripePayment($payment, $data);
                    break;
                case OrderPayment::METHOD_PAYPAL:
                    // $this->processPaypalPayment($payment, $data);
                    break;
                case OrderPayment::METHOD_VENDOR_CREDIT:
                    // $this->processVendorCreditPayment($payment);
                    break;
                default:
                    // throw new PaymentProcessingException("Unsupported payment method: {$payment->method}");
            }
        } catch (\Exception $e) {
            $payment->update([
                'status' => OrderPayment::STATUS_FAILED,
                'gateway_response' => ['error' => $e->getMessage()]
            ]);
            // throw new PaymentProcessingException($e->getMessage(), 0, $e);
        }
    }

    protected function processStripePayment(OrderPayment $payment, array $data): void
    {
        // $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
        
        // $charge = $stripe->charges->create([
        //     'amount' => (int) ($payment->amount * 100),
        //     'currency' => strtolower($payment->currency_code),
        //     'source' => $data['token'],
        //     'description' => 'Order #' . $payment->order->order_number,
        // ]);

        // $payment->markAsPaid($charge->id);
        // $payment->update(['gateway_response' => $charge->toArray()]);
    }

    protected function getVendorPaymentMethod(User $vendor, string $defaultMethod): string
    {
        return $vendor->preferred_payment_method ?? $defaultMethod;
    }

    protected function calculateVendorAmount(float $amount, User $vendor): float
    {
        $commissionRate = $vendor->commission_rate ?? config('vendors.default_commission', 0.15);
        return $amount * (1 - $commissionRate);
    }

    protected function calculatePlatformFee(float $amount, User $vendor): float
    {
        $commissionRate = $vendor->commission_rate ?? config('vendors.default_commission', 0.15);
        return $amount * $commissionRate;
    }
}