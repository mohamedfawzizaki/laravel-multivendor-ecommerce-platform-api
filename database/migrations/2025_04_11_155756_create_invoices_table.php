<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('invoice_number', 100)->unique();
            $table->date('invoice_date')->default(DB::raw('CURRENT_DATE'));
            $table->timestamp('start_date')->nullable(); // Discount start date
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0.00);
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->decimal('total', 10, 2);
            $table->enum('status', ['unpaid', 'paid', 'refunded', 'cancelled'])->default('unpaid');
            $table->string('pdf_path', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->string('currency_code', 10)->default('USD');
            $table->foreign('currency_code')->references('code')->on('currencies');
            
            // Indexes
            $table->index('order_id', 'idx_invoice_order');
            $table->index('invoice_number', 'idx_invoice_number');
        });
        
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');

            $table->string('product_name');
            $table->string('sku', 100)->nullable();

            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('price', 10, 2);
            $table->decimal('tax_rate', 5, 2)->default(0.00);
            $table->decimal('discount', 10, 2)->default(0.00);
            $table->decimal('total', 10, 2);

            $table->timestamps();

            $table->index('invoice_id', 'idx_invoice_item_invoice_id');
            $table->index('product_id', 'idx_invoice_item_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};