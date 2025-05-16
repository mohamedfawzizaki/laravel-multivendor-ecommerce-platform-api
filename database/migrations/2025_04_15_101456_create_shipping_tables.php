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
        /**
         * Table: shipping_addresses
         * 
         * Table Description:
         * Stores validated shipping addresses for users and orders. Maintains address history
         * with soft deletes and supports international address formats.
         * 
         * Workflow Role:
         * - Primary address storage for order fulfillment
         * - Manages default addresses for user preferences
         * - Enables address validation against city database
         * 
         * Key Workflow Processes:
         * - Address validation during checkout
         * - Default address selection
         * - Historical address preservation
         * 
         * Columns:
         * - Relationships: user_id (users.uuid), city_id (cities.id)
         * - Address Details: address_line1, address_line2, postal_code
         * - Recipient Info: recipient_name, recipient_phone, company_name
         * - Preferences: is_default
         * - Timestamps: created_at, updated_at, deleted_at
         * 
         * Note:
         * - city_id restriction prevents deletion of referenced cities
         * - Composite indexes optimize regional shipping queries
         */
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

        /**
         * Table: shipping_carriers
         * 
         * Table Description:
         * Master list of available shipping carriers. Supports both platform-wide and
         * vendor-specific carrier configurations.
         * 
         * Workflow Role:
         * - Central carrier configuration repository
         * - Enables carrier API integrations
         * - Manages carrier service level definitions
         * 
         * Key Workflow Processes:
         * - Carrier service setup and maintenance
         * - Tracking URL management
         * - Carrier availability toggling
         * 
         * Columns:
         * - Relationships: vendor_id (users.uuid)
         * - Identification: code, name
         * - Contact Info: customer_service_phone/email, website_url
         * - Configuration: tracking_url_format, service_levels (JSON)
         * - Status: is_active
         * - Timestamps: created_at, updated_at, deleted_at
         * 
         * Note:
         * - Nullable vendor_id indicates platform-wide carriers
         * - Unique constraints prevent duplicate carrier entries
         * - JSON service_levels stores available shipping tiers
         */
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

        /**
         * Table: shipping_methods
         * 
         * Table Description:
         * Configurable shipping options available for vendors. Supports complex pricing
         * models and carrier integrations.
         * 
         * Workflow Role:
         * - Defines available shipping options at checkout
         * - Calculates shipping costs based on multiple factors
         * - Manages shipping rule exceptions
         * 
         * Key Workflow Processes:
         * - Real-time shipping cost calculation
         * - Order eligibility validation
         * - Carrier API rate negotiation
         * 
         * Columns:
         * - Relationships: vendor_id (users.uuid), carrier_id, created/updated_by
         * - Identification: name, code, external_id
         * - Pricing: calculation_type, base_price, rate_table (JSON)
         * - Delivery: min/max_delivery_days, weekend_delivery
         * - Constraints: supported_zones, excluded_products (JSON)
         * - Fees: vendor_fee, platform_fee, tax_rate
         * - Status: is_active, is_default, is_integrated
         * - Timestamps: created_at, updated_at, deleted_at
         * 
         * Note:
         * - JSON fields enable complex configuration storage
         * - Audit fields track method creation/modification
         * - Indexes optimize checkout shipping option queries
         */
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('vendor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('carrier_id')->nullable()->constrained('shipping_carriers')->onDelete('set null');

            // Method identification
            $table->string('name', 100);
            $table->string('code', 50)->comment('Internal reference code');
            $table->string('external_id')->nullable()->comment('Carrier API identifier');

            // Pricing configuration
            $table->enum('calculation_type', [
                'flat_rate',
                'weight_based',
                'price_based',
                'carrier_api',
                'free'
            ])->default('flat_rate')->index();

            $table->decimal('base_price', 10, 2);
            $table->decimal('min_order_amount', 10, 2)->nullable()->comment('Minimum cart value for availability');
            $table->decimal('max_order_weight', 10, 3)->nullable();
            $table->json('rate_table')->nullable()->comment('JSON structure for weight/price-based rates');

            // Delivery details
            $table->unsignedSmallInteger('min_delivery_days');
            $table->unsignedSmallInteger('max_delivery_days');
            $table->boolean('weekend_delivery')->default(false);
            $table->boolean('cash_on_delivery')->default(false);

            // Service constraints
            $table->json('supported_zones')->nullable()->comment('JSON array of supported regions/countries');
            $table->json('excluded_products')->nullable()->comment('JSON array of product IDs');
            $table->json('carrier_config')->nullable()->comment('Carrier-specific settings');

            // Status & visibility
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_integrated')->default(false)->comment('API-based carrier integration');

            // Commission & fees
            $table->decimal('vendor_fee', 10, 2)->default(0);
            $table->decimal('platform_fee', 10, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);

            // Audit fields
            $table->foreignUuid('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // Indexes & Constraints
            $table->unique(['vendor_id', 'code']);
            $table->index(['carrier_id', 'is_active']);
        });

        /**
         * Table: shipments
         * 
         * Table Description:
         * Tracks physical package movement from warehouse to customer. Manages
         * carrier interactions and delivery proof.
         * 
         * Workflow Role:
         * - Central shipment lifecycle tracking
         * - Carrier communication hub
         * - Delivery exception handling
         * 
         * Key Workflow Processes:
         * - Label generation and tracking
         * - Shipping cost reconciliation
         * - Delivery status updates via API/webhooks
         * 
         * Columns:
         * - Relationships: vendor_order_id, carrier_id, shipping_address_id
         * - Tracking: tracking_number, service_level
         * - Costs: shipping_cost, insurance_cost
         * - Package Details: package_weight
         * - Status Timeline: status, [event timestamps]
         * - Delivery Estimates: estimated_delivery_date
         * - Timestamps: created_at, updated_at, deleted_at
         * 
         * Note:
         * - Multiple timestamps track shipment milestones
         * - Indexes optimize tracking lookup and status reports
         * - Restricted deletes preserve shipping history
         */
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
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
                'shipped',
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
            $table->unique('vendor_order_id');
            $table->index('status');
            $table->index('tracking_number');
            $table->index(['user_id', 'status']);
            $table->index('estimated_delivery_date');
        });

        /**
         * Table: shipment_events
         * 
         * Table Description:
         * Audit trail of shipment status changes and tracking updates. Provides
         * detailed delivery history for customer support.
         * 
         * Workflow Role:
         * - Immutable record of shipment progress
         * - Data source for tracking timelines
         * - Evidence for delivery disputes
         * 
         * Key Workflow Processes:
         * - Automated tracking update logging
         * - Exception event recording
         * - Delivery confirmation storage
         * 
         * Columns:
         * - Relationships: shipment_id
         * - Event Details: status, description, location
         * - Timing: occurred_at
         * - Timestamps: created_at, updated_at
         * 
         * Note:
         * - occurred_at preserves original event timing
         * - Composite index enables chronological event queries
         * - Description field captures carrier-specific messages
         */
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
        Schema::dropIfExists('shipping_methods');
        Schema::dropIfExists('shipping_carriers');
        Schema::dropIfExists('shipping_addresses');
    }
};