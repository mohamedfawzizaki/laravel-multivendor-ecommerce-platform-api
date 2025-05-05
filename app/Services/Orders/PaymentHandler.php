<?php

namespace App\Services\Orders;

use Exception;
use Throwable;
use App\Models\User;
use App\Models\Orders\Order;
use App\Models\Payments\Payment;
use Illuminate\Support\Facades\DB;
use App\Models\Orders\OrderPayment;
use App\Exceptions\PaymentProcessingException;

class PaymentHandler 
{
    public function initializePayment(Order $order): Payment
    {
        return Payment::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'amount' => 0, // Updated later
            'currency' => 'USD',
            'payment_status' => 'initializing',
            'payment_method' => 'unset'
        ]);
    }

    public function processGatewayPayment(Payment $payment, array $paymentData): void
    {
        // try {
        //     $gateway = new PaymentGateway(config('payment.gateway'));
        //     $response = $gateway->charge([
        //         'amount' => $payment->amount,
        //         'currency' => $payment->currency,
        //         'source' => $paymentData['token']
        //     ]);

        //     $this->recordTransaction(
        //         $payment,
        //         $response,
        //         $paymentData
        //     );

        //     if ($response->successful()) {
        //         $payment->update([
        //             'payment_status' => 'captured',
        //             'gateway_reference' => $response->transactionId,
        //             'captured_at' => now()
        //         ]);
        //     }

        // } catch (PaymentException $e) {
        //     $this->recordPaymentError($payment, $e);
        //     throw $e;
        // }
    }

    private function recordTransaction(Payment $payment, $response, $paymentData): void
    {
        // PaymentTransaction::create([
        //     'payment_id' => $payment->id,
        //     'transaction_type' => 'authorization',
        //     'amount' => $payment->amount,
        //     'currency_code' => $payment->currency,
        //     'gateway_transaction_id' => $response->transactionId,
        //     'gateway_status' => $response->status,
        //     'gateway_response' => $response->raw(),
        //     'is_success' => $response->successful(),
        //     'metadata' => [
        //         'ip' => request()->ip(),
        //         'user_agent' => request()->userAgent()
        //     ]
        // ]);
    }

    private function recordPaymentError(Payment $payment, Throwable $error): void
    {
        // PaymentError::create([
        //     'payment_id' => $payment->id,
        //     'user_id' => $payment->user_id,
        //     'order_id' => $payment->order_id,
        //     'error_type' => class_basename($error),
        //     'error_code' => $error->getCode(),
        //     'error_message' => $error->getMessage(),
        //     'is_recoverable' => $error instanceof RecoverablePaymentException
        // ]);
    }
}