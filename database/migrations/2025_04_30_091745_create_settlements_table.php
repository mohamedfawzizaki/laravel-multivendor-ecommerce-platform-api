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
         * Table: settlements
         * 
         * Table Description:
         * Central hub for vendor payout management. Tracks settlement periods, financial calculations,
         * and payment execution status for vendor earnings.
         * 
         * Workflow Role:
         * - Generates periodic vendor payout reports
         * - Manages payout approval workflows
         * - Tracks multi-currency settlements
         * 
         * Key Workflow Processes:
         * - Automatic settlement period closure
         * - Net amount calculation after commissions/fees
         * - Payout method validation
         * 
         * Columns:
         * - Identification: settlement_uuid (public ID)
         * - Relationships: vendor_id, processed_by
         * - Period: start_date, end_date, payout_date
         * - Financials: total_sales, total_refunds, net_amount
         * - Currency: currency_code, exchange_rate
         * - Payout: payout_method, payout_reference, status
         * - Audit: notes, metadata, soft deletes
         * 
         * Note:
         * - settlement_uuid used in vendor-facing communications
         * - Supports multi-currency conversions via exchange_rate
         * - status transitions control payout workflows
         */
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->string('settlement_uuid')->unique()->comment('Public facing identifier');
            $table->foreignUuid('vendor_id')->constrained('users')->onDelete('cascade');

            // Settlement period
            $table->date('start_date');
            $table->date('end_date');
            $table->date('payout_date')->nullable();

            // Financial summary
            $table->decimal('total_sales', 12, 2);
            $table->decimal('total_refunds', 12, 2)->default(0);
            $table->decimal('total_commission', 12, 2);
            $table->decimal('total_fees', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2);

            // Currency information
            $table->string('currency_code', 3)->default('USD');
            $table->foreign('currency_code')->references('code')->on('currencies');
            $table->decimal('exchange_rate', 12, 6)->default(1.0);

            // Payment details
            $table->enum('payout_method', [
                'bank_transfer',
                'paypal',
                'stripe_connect',
                'vendor_balance',
                'wire_transfer'
            ])->default('bank_transfer');

            $table->string('payout_reference')->nullable()->comment('External payment ID');
            $table->enum('status', [
                'pending',
                'processing',
                'paid',
                'failed',
                'disputed',
                'on_hold'
            ])->default('pending');

            // Audit fields
            $table->foreignUuid('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['vendor_id', 'status']);
            $table->index('settlement_uuid');
            $table->index(['start_date', 'end_date']);
        });

        /**
         * Table: settlement_items
         * 
         * Table Description:
         * Line-item details of transactions included in settlements. Provides transaction-level
         * breakdown of sales, refunds, and adjustments.
         * 
         * Workflow Role:
         * - Audits individual settlement components
         * - Enables detailed financial reporting
         * - Links orders/payments to settlements
         * 
         * Key Workflow Processes:
         * - Transaction aggregation into settlements
         * - Commission calculation verification
         * - Dispute resolution evidence
         * 
         * Columns:
         * - Relationships: settlement_id, order_payment_id
         * - Financials: gross_amount, commission, fees
         * - Transaction: type, transaction_date
         * - References: order_id, transaction_id
         * 
         * Note:
         * - type determines financial impact on settlement
         * - Supports partial settlement inclusions
         * - transaction_id links to payment gateway records
         */
        Schema::create('settlement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')->constrained('settlements')->onDelete('cascade');
            $table->foreignId('vendor_order_id')->nullable()->constrained('vendor_orders')->onDelete('cascade');

            // Transaction details
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('commission', 12, 2);
            $table->decimal('fees', 12, 2);
            $table->decimal('net_amount', 12, 2);

            // Transaction type
            $table->enum('type', [
                'sale',
                'refund',
                'chargeback',
                'adjustment',
                'fee'
            ])->default('sale');

            // Reference information
            $table->string('order_id')->nullable();
            $table->string('transaction_id')->nullable();
            $table->date('transaction_date');

            $table->timestamps();

            // Indexes
            $table->index(['settlement_id', 'type']);
            $table->index('transaction_id');
        });

        /**
         * Table: settlement_adjustments
         * 
         * Table Description:
         * Manual adjustments to settlement amounts. Records corrections, penalties,
         * and bonus payments outside normal transactions.
         * 
         * Workflow Role:
         * - Handles settlement disputes and corrections
         * - Tracks manual financial adjustments
         * - Maintains audit trail for balance changes
         * 
         * Key Workflow Processes:
         * - Dispute resolution adjustments
         * - Commission structure corrections
         * - Vendor penalty/bonus applications
         * 
         * Columns:
         * - Relationships: settlement_id, adjusted_by
         * - Adjustment: type, amount, description
         * - References: reference_id, reference_type
         * 
         * Note:
         * - type categorizes adjustment reason
         * - reference_id links to related entities
         * - Requires authorization workflow for changes
         */
        Schema::create('settlement_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')->constrained('settlements')->onDelete('cascade');

            // Adjustment details
            $table->decimal('amount', 12, 2);
            $table->enum('type', [
                'commission',
                'fee',
                'refund',
                'chargeback',
                'bonus',
                'penalty'
            ]);

            $table->text('description');
            $table->foreignUuid('adjusted_by')->nullable()->constrained('users')->onDelete('set null');

            // Reference fields
            $table->string('reference_id')->nullable();
            $table->string('reference_type')->nullable()->comment('order/transaction/dispute ID');

            $table->timestamps();

            // Indexes
            $table->index(['settlement_id', 'type']);
        });

        /**
         * Table: settlement_disputes
         * 
         * Table Description:
         * Manages vendor disputes regarding settlement calculations. Tracks dispute
         * resolution lifecycle from initiation to closure.
         * 
         * Workflow Role:
         * - Formalizes vendor settlement challenges
         * - Documents resolution processes
         * - Prevents payout during active disputes
         * 
         * Key Workflow Processes:
         * - Dispute initiation and triage
         * - Multi-stage resolution workflows
         * - Settlement hold management
         * 
         * Columns:
         * - Relationships: settlement_id, raised_by, resolved_by
         * - Dispute: reason, status, resolution_notes
         * - Timestamps: resolved_at
         * 
         * Note:
         * - Open disputes block settlement payouts
         * - status controls escalation paths
         * - resolution_notes required for closure
         */
        Schema::create('settlement_disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')->constrained('settlements')->onDelete('cascade');
            $table->foreignUuid('raised_by')->constrained('users')->onDelete('cascade');

            // Dispute details
            $table->text('reason');
            $table->enum('status', [
                'open',
                'under_review',
                'resolved',
                'escalated',
                'closed'
            ])->default('open');

            // Resolution details
            $table->text('resolution_notes')->nullable();
            $table->foreignUuid('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['settlement_id', 'status']);
        });

        /**
         * Table: vendor_payout_methods
         * 
         * Table Description:
         * Securely stores vendor payment preferences and verification status.
         * Supports multiple payout methods per vendor.
         * 
         * Workflow Role:
         * - Manages payment destination configurations
         * - Enables payout method verification
         * - Stores encrypted financial details
         * 
         * Key Workflow Processes:
         * - Payout method enrollment
         * - PCI-compliant detail storage
         * - Verification workflows
         * 
         * Columns:
         * - Relationships: vendor_id, verified_by
         * - Method: type, details (encrypted)
         * - Status: is_primary, is_verified
         * - Verification: verified_at
         * 
         * Note:
         * - details field requires encryption at rest
         * - Verified methods only used for payouts
         * - Soft deletes maintain payment history
         */
        Schema::create('vendor_payout_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('vendor_id')->constrained('users')->onDelete('cascade');

            // Method details
            $table->enum('type', [
                'bank_account',
                'paypal',
                'stripe_connect',
                'vendor_balance'
            ]);

            $table->json('details')->comment('Encrypted payment method details');
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_verified')->default(false);

            // Verification details
            $table->timestamp('verified_at')->nullable();
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['vendor_id', 'type']);
        });

        /**
         * Table: settlement_logs
         * 
         * Table Description:
         * Immutable audit trail of settlement lifecycle events. Tracks all
         * modifications and system actions on settlements.
         * 
         * Workflow Role:
         * - Provides settlement change history
         * - Supports compliance audits
         * - Debugs payout issues
         * 
         * Key Workflow Processes:
         * - Settlement state change tracking
         * - User action monitoring
         * - System event logging
         * 
         * Columns:
         * - Relationships: settlement_id, performed_by
         * - Event: event_type, description
         * - Context: metadata
         * 
         * Note:
         * - Log entries cannot be modified
         * - metadata stores system-specific context
         * - Indexed for fast historical queries
         */
        Schema::create('settlement_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')->constrained('settlements')->onDelete('cascade');

            // Log details
            $table->enum('event_type', [
                'created',
                'processed',
                'paid',
                'failed',
                'adjusted',
                'disputed',
                'retried'
            ]);

            $table->text('description');
            $table->json('metadata')->nullable();
            $table->foreignUuid('performed_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();

            // Indexes
            $table->index(['settlement_id', 'event_type']);
        });

        /**
         * Table: commission_structures
         * 
         * Table Description:
         * Defines commission calculation rules for vendors. Supports flexible
         * commission models and historical tracking.
         * 
         * Workflow Role:
         * - Determines commission rates per vendor
         * - Manages tiered/category-based commissions
         * - Stores commission rule history
         * 
         * Key Workflow Processes:
         * - Commission rule application
         * - Rate schedule management
         * - Historical rate preservation
         * 
         * Columns:
         * - Relationships: vendor_id
         * - Structure: type, value, configuration
         * - Validity: valid_from, valid_to
         * - Status: is_active, is_default
         * 
         * Note:
         * - configuration stores tier/category rules
         * - Active/inactive status controls rate application
         * - Default structures apply when no vendor-specific exists
         */
        Schema::create('commission_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('vendor_id')->nullable()->constrained('users')->onDelete('cascade');

            // Commission details
            $table->string('name');
            $table->enum('type', [
                'percentage',
                'flat_rate',
                'tiered',
                'category_based'
            ])->default('percentage');

            $table->decimal('value', 10, 2);
            $table->json('configuration')->nullable()->comment('Tiered rates/category mappings');

            // Validity period
            $table->date('valid_from');
            $table->date('valid_to')->nullable();

            // Activation
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes
            $table->index(['vendor_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_structures');
        Schema::dropIfExists('settlement_logs');
        Schema::dropIfExists('vendor_payout_methods');
        Schema::dropIfExists('settlement_disputes');
        Schema::dropIfExists('settlement_adjustments');
        Schema::dropIfExists('settlement_items');
        Schema::dropIfExists('settlements');
    }
};