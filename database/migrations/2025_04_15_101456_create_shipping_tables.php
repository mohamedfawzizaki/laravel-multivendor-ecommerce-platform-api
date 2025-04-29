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
        Schema::create('shipping_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('city_id')->constrained('cities')->onDelete('restrict');

            $table->string('address_line1', 255);
            $table->string('address_line2', 255)->nullable();
            $table->string('postal_code', 20);
            $table->string('recipient_name', 100);
            $table->string('recipient_phone', 20);
            $table->string('company_name', 100)->nullable();
            $table->boolean('is_default')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['city_id']);
        });

        Schema::create('shipping_carriers', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('vendor_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('code', 10); // (e.g., 'fedex', 'ups')
            $table->string('name', 100);
            $table->string('tracking_url_format')->nullable();
            $table->string('customer_service_phone', 20)->nullable();
            $table->string('customer_service_email')->nullable();
            $table->string('website_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('service_levels')->nullable(); // Stores available service levels as JSON
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['vendor_id', 'code']);
            $table->unique(['vendor_id', 'name']);
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            // $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignUuid('vendor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('vendor_order_id')->constrained('vendor_orders')->onDelete('cascade');
            $table->foreignId('carrier_id')->constrained('shipping_carriers')->onDelete('restrict');
            $table->foreignId('shipping_address_id')->constrained('shipping_addresses')->onDelete('restrict');
            // Shipment details
            $table->string('tracking_number', 100)->unique();
            $table->decimal('shipping_cost', 12, 2)->default(0.00);
            $table->decimal('insurance_cost', 12, 2)->default(0.00);
            $table->decimal('package_weight', 10, 3)->nullable(); // in kilograms
            $table->string('service_level', 50)->nullable(); // e.g., 'express', 'standard'
            // Status tracking
            $table->enum('status', [
                'label_created',
                'pending',
                'in_transit',
                'out_for_delivery',
                'delivered',
                'exception',
                'returned',
                'cancelled'
            ])->default('label_created');
            // Timestamps
            $table->date('estimated_delivery_date')->nullable();
            $table->timestamp('label_created_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('out_for_delivery_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('last_tracking_update_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
            // Indexes
            $table->index('vendor_order_id');
            $table->index('status');
            $table->index('tracking_number');
            $table->index(['user_id', 'status']);
            $table->index('estimated_delivery_date');
        });

        Schema::create('shipment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->onDelete('cascade');

            $table->string('status', 50);
            $table->text('description')->nullable();
            $table->string('location', 100)->nullable();
            $table->timestamp('occurred_at');

            $table->timestamps();
            $table->index(['shipment_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_events');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('shipping_carriers');
        Schema::dropIfExists('shipping_addresses');
    }
};