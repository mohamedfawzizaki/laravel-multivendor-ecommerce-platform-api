<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->char('user_id', 26);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('vendor_order_id')->constrained('vendor_orders')->onDelete('cascade');

            // Amount fields
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('USD');
            $table->decimal('base_amount', 12, 2);
            $table->decimal('exchange_rate', 12, 6)->default(1.0);
            
            // Status and method
            $table->enum('payment_status', [
                'pending', 
                'authorized', 
                'captured', 
                'partially_refunded', 
                'fully_refunded', 
                'failed', 
                'voided',
                'disputed',
                'chargeback'
            ])->default('pending');
            
            $table->enum('payment_method', [
                'credit_card', 
                'debit_card', 
                'paypal', 
                'bank_transfer',
                'digital_wallet',
                'crypto',
                'cash_on_delivery',
                'installment'
            ]);
            
            // Payment details
            $table->json('payment_method_details')->nullable();
            $table->string('payment_gateway');
            $table->string('gateway_reference')->unique();
            $table->unsignedTinyInteger('fraud_score')->nullable();
            
            // Timestamps
            $table->timestamp('captured_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->index('order_id');
            $table->index('payment_status');
            $table->index('gateway_reference');
            $table->index('created_at');
            $table->index('payment_gateway');
        });

        Schema::create('payment_refunds', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('order_payment_id')->constrained('order_payments')->onDelete('cascade');
            $table->foreignUuid('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignUuid('vendor_id')->constrained('users')->onDelete('cascade');
            
            // Refund details
            $table->decimal('amount', 12, 2);
            $table->string('currency_code', 10)->default('USD');
            $table->foreign('currency_code')->references('code')->on('currencies');
            
            // Status tracking
            $table->enum('status', [
                'requested',
                'processing',
                'completed',
                'failed',
                'rejected'
            ])->default('requested');
            
            // Refund method (must match original payment method)
            $table->enum('method', [
                'original',
                'credit',
                'manual_refund',
                'other'
            ])->default('original');
            
            // References
            $table->string('refund_reason')->nullable();
            $table->text('customer_notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('transaction_id')->nullable()->comment('External refund reference');
            
            // Timestamps
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('estimated_refund_date')->nullable();
            
            // Gateway response
            $table->json('gateway_response')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['order_payment_id', 'status']);
            $table->index(['vendor_id', 'status']);
            $table->index(['method', 'status']);
            $table->index('processed_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
};