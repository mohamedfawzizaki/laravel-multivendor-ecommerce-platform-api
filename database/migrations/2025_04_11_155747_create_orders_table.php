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
            // Shipping information
            // $table->foreignId('shipping_address_id')->nullable()->constrained('addresses');
            
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
        
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants');
            
            // Pricing
            $table->unsignedInteger('quantity');
            $table->decimal('price', 10, 2)->comment('Snapshot of price at time of purchase');
            $table->decimal('subtotal', 10, 2)->storedAs('price * quantity');
            
            $table->timestamps();
            
            $table->index(['order_id', 'product_id']);
        });
        
        Schema::create('currencies', function (Blueprint $table) {
            $table->string('code', 10)->primary();  // ISO 4217 currency code (e.g., USD, EUR)
            $table->string('name', 100);  // Full name of the currency (e.g., United States Dollar)
            $table->string('symbol', 10);  // Symbol of the currency (e.g., $, €, £)
            $table->decimal('exchange_rate', 15, 6)->default(1.000000);  // Exchange rate relative to base currency

            $table->timestamps();  // created_at, updated_at timestamps
        });
        
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            // Relationships
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            // Payment details
            $table->enum('method', [
                'credit_card', 
                'paypal', 
                'stripe', 
                'bank_transfer', 
                'cash'
            ])->index();
            
            $table->enum('status', [
                'pending', 
                'authorized', 
                'paid', 
                'failed', 
                'refunded'
            ])->default('pending');
            
            $table->string('transaction_id')->unique()->nullable();
            $table->decimal('amount', 10, 2);
            
            $table->string('currency_code', 10)->default('USD');
            $table->foreign('currency_code')->references('code')->on('currencies');
            
            // Audit fields
            $table->timestamp('processed_at')->nullable();
            $table->json('gateway_response')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['order_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_payments');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');

    }
};