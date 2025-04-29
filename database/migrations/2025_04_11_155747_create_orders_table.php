<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            // Order identification
            $table->string('order_number')->unique()->comment('User-facing order ID');
            // Financials
            $table->decimal('subtotal', 10, 2)->comment('Amount before taxes/fees');
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2);
            // Status tracking
            $table->enum('status', [
                'pending',
                'processing',
                'shipped',
                'delivered',
                'cancelled',
                'refunded'
            ])->default('pending')->index();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'status']);
        });

        Schema::create('vendor_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignUuid('vendor_id')->constrained('users')->onDelete('cascade'); // The seller
        
            $table->string('vendor_order_number')->unique()->comment('Vendor-specific order number');
        
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2);
        
            $table->enum('status', [
                'pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'
            ])->default('pending')->index();
        
            $table->timestamps();
            $table->softDeletes();
        
            $table->index(['vendor_id', 'status']);
        });
        
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            // Relationships
            $table->foreignId('vendor_order_id')->constrained('vendor_orders')->onDelete('cascade'); // belongs to a vendor order
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('variation_id')->nullable()->constrained('product_variations');
            // Pricing
            $table->unsignedInteger('quantity');
            $table->decimal('price', 10, 2)->comment('Snapshot of price at time of purchase');
            $table->decimal('subtotal', 10, 2)->storedAs('price * quantity');

            $table->timestamps();

            $table->index(['vendor_order_id', 'product_id']);
        });

        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            // Relationships
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignUuid('vendor_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('parent_payment_id')->nullable()->constrained('order_payments')->onDelete('cascade');
            // Payment details
            $table->enum('method', [
                'credit_card',
                'paypal',
                'stripe',
                'bank_transfer',
                'cash',
                'vendor_credit',
                'split_payment' // New method type for parent payments
            ])->index();

            $table->enum('status', [
                'pending',
                'authorized',
                'paid',
                'failed',
                'refunded',
                'partially_refunded',
                'split_pending',
                'settlement_pending' // New status for vendor settlement
            ])->default('pending');
        
            $table->string('transaction_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->decimal('vendor_amount', 12, 2)->nullable()->comment('Amount after commission/fees');
            $table->decimal('platform_fee', 12, 2)->default(0);
            
            $table->string('currency_code', 10)->default('USD');
            $table->foreign('currency_code')->references('code')->on('currencies');
            
            // Split payment details
            $table->boolean('is_split_payment')->default(false);
            $table->json('split_details')->nullable()->comment('JSON with vendor payment distribution');
            
            // Payment hierarchy
            $table->enum('payment_type', [
                'parent',
                'child',
                'standalone'
            ])->default('standalone')->comment('Type of payment in hierarchy');
            
            // Audit fields
            $table->timestamp('processed_at')->nullable();
            $table->json('gateway_response')->nullable();
            $table->json('vendor_settlement_details')->nullable();
        
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['order_id', 'vendor_id', 'status']);
            $table->index(['vendor_id', 'status']);
            $table->index(['transaction_id', 'method']);
            $table->index(['parent_payment_id', 'payment_type']);
            $table->index(['is_split_payment', 'payment_type']);
        });

        Schema::create('vendor_settlements', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignUuid('vendor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('order_payment_id')->constrained('order_payments')->onDelete('cascade');
            
            // Settlement details
            $table->decimal('amount', 12, 2);
            $table->string('currency_code', 10)->default('USD');
            $table->foreign('currency_code')->references('code')->on('currencies');
            
            // Payment method (how vendor receives funds)
            $table->enum('method', [
                'bank_transfer',
                'paypal',
                'stripe_connect',
                'vendor_balance',
                'check',
                'other'
            ])->default('bank_transfer');
            
            // Status tracking
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'on_hold'
            ])->default('pending');
            
            // Transaction references
            $table->string('transaction_id')->nullable()->comment('External payout reference');
            $table->string('bank_reference')->nullable()->comment('Bank transfer reference');
            
            // Timestamps
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('estimated_payout_date')->nullable();
            
            // Additional info
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable()->comment('Additional payment gateway data');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['vendor_id', 'status']);
            $table->index(['order_payment_id', 'status']);
            $table->index(['method', 'status']);
            $table->index('estimated_payout_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_settlements');
        Schema::dropIfExists('order_payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('vendor_orders');
        Schema::dropIfExists('orders');
    }
};