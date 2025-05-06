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
         * Table: orders
         * 
         * Table Description:
         * Core table storing customer order headers and financial summaries. Acts as the central 
         * order record linking users to vendor-specific order splits.
         * 
         * Workflow Role:
         * - Created during checkout completion
         * - Manages overall order lifecycle and financial aggregation
         * - Primary entity for customer-facing order tracking
         * - Handles system-wide fraud detection and review processes
         * 
         * Key Workflow Processes:
         * - Initial order creation with payment authorization
         * - Fraud risk assessment and manual review handling
         * - Status aggregation from child vendor orders
         * - Final order closure and archival
         * 
         * Columns:
         * - Relationships: user_id (users.uuid)
         * - Identification: order_number (unique)
         * - Financials: subtotal, tax, total_price
         * - Status: status (enum with index)
         * - Fraud: fraud_score, flagged_for_review
         * - Timestamps: created_at, updated_at, deleted_at
         * 
         * Note:
         * - Uses soft deletes for order history preservation
         * - Composite index on user_id+status for dashboard queries
         */
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

            // Fraud detection
            // $table->unsignedTinyInteger('fraud_score')->nullable();
            // $table->boolean('flagged_for_review')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'status']);
        });

        /**
         * Table: vendor_orders
         * 
         * Table Description:
         * Manages vendor-specific order splits and fulfillment details. Each record represents
         * a vendor's portion of a parent order with independent processing.
         * 
         * Workflow Role:
         * - Created when splitting orders to multiple vendors
         * - Tracks vendor-specific fulfillment progress
         * - Manages commission calculations and payouts
         * 
         * Key Workflow Processes:
         * - Order splitting logic based on vendor products
         * - Vendor-facing order processing workflows
         * - Commission calculation upon delivery
         * - Partial refund handling at vendor level
         * 
         * Columns:
         * - Relationships: order_id (orders.id), vendor_id (users.uuid)
         * - Identification: vendor_order_number (unique)
         * - Financials: subtotal, tax, commission_amount, total_price
         * - Status: status (extended enum with index)
         * - Operations: fulfillment_type, vendor_notes
         * - Timestamps: created_at, updated_at, deleted_at
         * 
         * Note:
         * - Supports mixed fulfillment models (vendor vs platform shipped)
         * - JSON vendor_notes allows structured data storage
         */
        Schema::create('vendor_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignUuid('vendor_id')->constrained('users')->onDelete('cascade'); // The seller

            $table->string('vendor_order_number')->unique()->comment('Vendor-specific order number');

            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('commission_amount', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2);

            $table->enum('status', [
                'pending',
                'processing',
                'shipped',
                'delivered',
                'cancelled',
                'refunded',
                'partially_refunded',
                'on_hold'
            ])->default('pending')->index();

            // Vendor specific
            $table->enum('fulfillment_type', ['vendor', 'platform'])->default('vendor');
            $table->json('vendor_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['vendor_id', 'status']);
            // $table->unique(['vendor_id', 'order_id']);
        });

        /**
         * Table: order_items
         * 
         * Table Description:
         * Granular line-item details for products within orders. Maintains historical pricing
         * and manages digital/physical fulfillment specifics.
         * 
         * Workflow Role:
         * - Represents individual product purchases
         * - Manages inventory reservations and tracking
         * - Handles digital product access and returns
         * 
         * Key Workflow Processes:
         * - Inventory deduction on order confirmation
         * - Digital license generation and expiration
         * - Return eligibility calculations
         * - Partial fulfillment tracking
         * 
         * Columns:
         * - Relationships: order_id, vendor_order_id, product_id, variation_id
         * - Quantities: quantity, price (snapshot), subtotal (calculated)
         * - Status: status (item-level tracking)
         * - Digital: is_digital, download_url, download_expiry
         * - Returns: is_returnable, return_by_date
         * - Timestamps: created_at, updated_at
         * 
         * Note:
         * - Price snapshots preserve historical accuracy
         * - Stored calculated subtotal improves query performance
         * - Return windows consider product type and policy rules
         */
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            // Relationships
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('vendor_order_id')->constrained('vendor_orders')->onDelete('cascade'); // belongs to a vendor order
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('variation_id')->nullable()->constrained('product_variations');
            // Item details
            // $table->string('product_name');
            // $table->string('sku');
            // $table->json('attributes')->nullable()->comment('Selected variation attributes');
            // Pricing
            $table->unsignedInteger('quantity');
            $table->decimal('price', 10, 2)->comment('Snapshot of price at time of purchase');
            $table->decimal('subtotal', 10, 2)->storedAs('price * quantity');

            // Status
            $table->enum('status', [
                'pending',
                'fulfilled',
                'shipped',
                'delivered',
                'returned',
                'refunded',
                'cancelled'
            ])->default('pending');

            // Digital product specific
            $table->boolean('is_digital')->default(false);
            $table->text('download_url')->nullable();
            $table->timestamp('download_expiry')->nullable();

            // Return/refund tracking
            $table->boolean('is_returnable')->default(true);
            $table->timestamp('return_by_date')->nullable();

            $table->timestamps();

            $table->index(['vendor_order_id', 'product_id']);
        });

        Schema::create('tax_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. VAT, Eco Tax
            $table->decimal('rate', 5, 2); // e.g. 10.00
            $table->boolean('is_inclusive')->default(false);
            $table->string('tax_type')->nullable(); // e.g. VAT, GST
            $table->string('tax_id')->nullable(); // e.g. external authority reference
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        
        /**
         * Table: order_taxes
         * 
         * Table Description:
         * Manages tax calculations and compliance records for orders. Supports multiple tax rates
         * and types at both order and vendor order levels.
         * 
         * Workflow Role:
         * - Stores detailed tax breakdowns for financial compliance
         * - Supports inclusive/exclusive tax pricing models
         * - Maintains audit trail for tax calculations
         * 
         * Key Workflow Processes:
         * - Automatic tax calculation during checkout
         * - Tax reporting and documentation generation
         * - Handling regional tax regulations and exemptions
         * 
         * Columns:
         * - Relationships: order_id, vendor_order_id (optional)
         * - Tax Details: tax_name, tax_rate, tax_amount
         * - Tax Identification: tax_id, tax_type
         * - Pricing Model: is_inclusive (tax included in price)
         * - Timestamps: created_at, updated_at
         * 
         * Note:
         * - Nullable vendor_order_id allows order-level taxes
         * - Supports multiple tax types per order (VAT + local taxes)
         * - Tax rate precision supports global tax requirements
         */
        Schema::create('order_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('vendor_order_id')->nullable()->constrained('vendor_orders')->onDelete('cascade');
            $table->foreignId('tax_rule_id')->nullable()->constrained('tax_rules')->onDelete('cascade');

            $table->string('tax_name');
            $table->decimal('tax_rate', 5, 2);
            $table->decimal('tax_amount', 10, 2);
            $table->boolean('is_inclusive')->default(false);

            // Tax identification
            $table->string('tax_id')->nullable()->comment('Tax agency identifier');
            $table->string('tax_type')->nullable()->comment('Type of tax (VAT, GST, etc.)');

            $table->timestamps();

            // Indexes
            $table->index(['order_id', 'vendor_order_id']);
        });

        /**
         * Table: order_commissions
         * 
         * Table Description:
         * Tracks vendor commission calculations and payment status. Enables multi-vendor
         * marketplace financial settlements.
         * 
         * Workflow Role:
         * - Manages vendor payout calculations
         * - Tracks commission payment lifecycle
         * - Supports flexible commission structures
         * 
         * Key Workflow Processes:
         * - Commission calculation on order completion
         * - Vendor payout batch processing
         * - Commission dispute resolution
         * 
         * Columns:
         * - Relationships: vendor_order_id, vendor_id
         * - Financials: amount, rate, commission_type
         * - Payment Tracking: is_paid, paid_date
         * - Timestamps: created_at, updated_at
         * 
         * Note:
         * - Rate field supports both percentage and fixed amounts
         * - Paid_date index enables payment period reporting
         * - Maintains historical commission rates for audit purposes
         */
        Schema::create('order_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_order_id')->constrained('vendor_orders')->onDelete('cascade');
            $table->foreignUuid('vendor_id')->constrained('users')->onDelete('cascade');

            $table->decimal('amount', 10, 2);
            $table->decimal('rate', 5, 2)->comment('Percentage rate applied');
            $table->string('commission_type')->default('percentage')->comment('percentage or fixed');
            $table->boolean('is_paid')->default(false);
            $table->date('paid_date')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['vendor_id', 'is_paid']);
            $table->index('paid_date');
        });

        /**
         * Table: order_notes
         * 
         * Table Description:
         * Stores contextual communication and operational notes related to orders. Supports
         * customer-facing and internal communication tracking.
         * 
         * Workflow Role:
         * - Maintains order history and context
         * - Facilitates team collaboration on orders
         * - Tracks system-generated notifications
         * 
         * Key Workflow Processes:
         * - Customer service communication logging
         * - Internal operational notes
         * - Automated system notifications
         * 
         * Columns:
         * - Relationships: order_id, user_id (nullable)
         * - Content: note, type, notify_customer
         * - Visibility: is_pinned (priority notes)
         * - Timestamps: created_at, updated_at
         * 
         * Note:
         * - System-generated notes have null user_id
         * - Pinned notes highlight critical order information
         * - Type classification enables filtered views
         */
        Schema::create('order_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignUuid('user_id')->nullable()->constrained('users')->onDelete('set null');

            $table->text('note');
            $table->enum('type', ['customer', 'internal', 'system'])->default('internal');
            $table->boolean('notify_customer')->default(false);
            $table->boolean('is_pinned')->default(false);

            $table->timestamps();

            // Indexes
            $table->index(['order_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_notes');
        Schema::dropIfExists('order_commissions');
        Schema::dropIfExists('order_taxes');
        Schema::dropIfExists('tax_rules');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('vendor_orders');
        Schema::dropIfExists('orders');
    }
};