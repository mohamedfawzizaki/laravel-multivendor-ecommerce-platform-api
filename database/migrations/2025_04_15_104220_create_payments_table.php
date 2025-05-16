<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Customer → Platform
        /**
         * Table: payments
         * 
         * Table Description:
         * Core payment processing records linking users, orders, and vendor orders. Tracks
         * payment lifecycle from authorization to settlement.
         * 
         * Workflow Role:
         * - Manages payment gateway interactions
         * - Tracks currency conversions
         * - Handles fraud scoring and payment statuses
         * 
         * Key Workflow Processes:
         * - Payment authorization and capture
         * - Multi-currency transaction handling
         * - Fraud detection integration
         * 
         * Columns:
         * - Relationships: user_id, order_id, vendor_order_id
         * - Financials: amount, currency, base_amount, exchange_rate
         * - Status: payment_status, payment_method
         * - Gateway: payment_gateway, gateway_reference
         * - Timestamps: captured_at, refunded_at, failed_at
         * 
         * Note:
         * - Stores both original and base currency amounts
         * - Gateway_reference ensures idempotent operations
         * - Fraud_score enables automated risk handling
         */
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');

            // Amount fields
            $table->decimal('amount', 12, 2);
            $table->string('currency_code', 10)->default('USD');
            $table->foreign('currency_code')->references('code')->on('currencies'); // Centralized currency rates/pricing
            // $table->decimal('base_amount', 12, 2);
            // $table->decimal('exchange_rate', 12, 6)->default(1.0);

            // Status and method
            $table->enum('payment_status', [
                'pending',
                'authorized',
                'captured',
                'paid',
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
            $table->string('payment_gateway'); // Payment Provider e.g. Stripe, PayPal
            $table->string('transaction_id')->nullable(); // Gateway reference
            $table->string('gateway_reference')->unique();
            $table->json('payment_method_details')->nullable();
            $table->unsignedTinyInteger('fraud_score')->nullable();

            // Timestamps
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('order_id');
            $table->index('payment_status');
            $table->index('gateway_reference');
            $table->index('created_at');
            $table->index('payment_gateway');
        });

        Schema::create('vendor_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('payment_account_id')->nullable()->constrained('vendor_payment_accounts')->nullOnDelete(); // Vendor's payout account
            $table->decimal('amount', 12, 2);
            $table->decimal('platform_commission', 12, 2)->default(0);
            $table->decimal('tax_withheld', 12, 2)->default(0);
            $table->enum('status', ['pending', 'processing', 'paid', 'failed'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->string('reference')->nullable(); // Gateway or bank ref
            $table->json('notes')->nullable();
            $table->timestamps();
        });

        /**
         * Table: payment_refunds
         * 
         * Table Description:
         * Comprehensive refund management system with RMA integration. Tracks full refund
         * lifecycle from request to reconciliation.
         * 
         * Workflow Role:
         * - Manages refund authorization and execution
         * - Links refunds to RMAs and specific items
         * - Tracks refund method restrictions
         * 
         * Key Workflow Processes:
         * - Refund request validation
         * - Partial refund handling
         * - Vendor settlement adjustments
         * 
         * Columns:
         * - Relationships: order_payment_id, processed_by, vendor_id
         * - Refund Details: amount, currency_code, method
         * - Status Tracking: status, processed_at
         * - References: rma_number, transaction_id
         * - Documentation: customer_notes, internal_notes
         * 
         * Note:
         * - method must match original payment method
         * - items_refunded JSON tracks partial refunds
         * - RMA number links to return merchandise process
         */
        Schema::create('payment_refunds', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignUuid('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignUuid('vendor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('order_item_id')->constrained('order_items')->onDelete('cascade');

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
                'rejected',
                'partially_completed'
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
            $table->string('rma_number')->nullable()->comment('Return Merchandise Authorization');

            // Timestamps
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('estimated_refund_date')->nullable();
            $table->timestamp('customer_notified_at')->nullable();

            // Gateway response
            $table->json('gateway_response')->nullable();
            $table->json('items_refunded')->nullable()->comment('JSON array of order items refunded');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['order_item_id', 'status']);
            $table->index(['vendor_id', 'status']);
            $table->index(['method', 'status']);
            $table->index('processed_at');
            $table->index('rma_number');
        });

        /**
         * Table: payment_transactions
         * 
         * Table Description:
         * Atomic transaction records for all payment activities. Provides complete audit
         * trail of financial operations.
         * 
         * Workflow Role:
         * - Tracks individual payment operations
         * - Stores gateway raw responses
         * - Manages 3D Secure and payment actions
         * 
         * Key Workflow Processes:
         * - Transaction lifecycle recording
         * - Chargeback and dispute evidence
         * - Settlement batch processing
         * 
         * Columns:
         * - Relationships: payment_id, parent_transaction_id
         * - Transaction: type, amount, currency_code
         * - Gateway: gateway_transaction_id, status, response
         * - Security: ip_address, user_agent
         * - Metadata: is_test, requires_action, action_url
         * 
         * Note:
         * - Supports transaction chaining (authorize -> capture)
         * - gateway_response stores raw API data
         * - Metadata used for debugging and compliance
         */
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->onDelete('cascade');
            $table->foreignId('parent_transaction_id')->nullable()->constrained('payment_transactions')->onDelete('set null');

            // Transaction details
            $table->enum('transaction_type', [
                'authorization',
                'capture',
                'refund',
                'void',
                'chargeback',
                'dispute',
                'adjustment',
                'settlement',
                'payout'
            ]);

            $table->decimal('amount', 12, 2);
            $table->string('currency_code', 3)->default('USD');
            $table->foreign('currency_code')->references('code')->on('currencies');
            $table->string('gateway_transaction_id');
            $table->string('gateway_status');
            $table->string('gateway_response_code')->nullable();
            $table->text('gateway_response')->nullable();

            // Status flags
            $table->boolean('is_success')->default(false);
            $table->boolean('requires_action')->default(false);
            $table->string('action_url', 512)->nullable();
            $table->boolean('is_test')->default(false);

            // Metadata
            $table->json('metadata')->nullable();
            $table->string('processed_by', 50)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('payment_id');
            $table->index('parent_transaction_id');
            $table->index('gateway_transaction_id');
            $table->index('created_at');
            $table->index('transaction_type');
            $table->index('is_success');
            $table->index('gateway_status');
        });

        /**
         * Table: payment_errors
         * 
         * Table Description:
         * Centralized error tracking system for payment failures. Enables automated
         * recovery workflows and error analytics.
         * 
         * Workflow Role:
         * - Classifies payment failures
         * - Powers automatic retry mechanisms
         * - Provides error rate monitoring
         * 
         * Key Workflow Processes:
         * - Error categorization and triage
         * - Fraud pattern detection
         * - Payment recovery workflows
         * 
         * Columns:
         * - Relationships: payment_id, user_id, order_id
         * - Error: type, code, message, recoverable
         * - Retry: retry_count
         * - Evidence: gateway_response
         * 
         * Note:
         * - error_type enables automated handling
         * - is_recoverable triggers retry workflows
         * - Tracks both user-facing and system errors
         */
        Schema::create('payment_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null');
            $table->char('user_id', 26);
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');

            // Error details
            $table->enum('error_type', [
                'validation',
                'gateway',
                'fraud',
                'authentication',
                'insufficient_funds',
                'system',
                'timeout',
                'unknown'
            ]);

            $table->string('error_code', 50);
            $table->text('error_message');
            $table->json('gateway_response')->nullable();
            $table->boolean('is_recoverable')->default(false);
            $table->unsignedTinyInteger('retry_count')->default(0);

            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index('user_id');
            $table->index('order_id');
            $table->index('payment_id');
            $table->index('error_type');
            $table->index('error_code');
            $table->index('created_at');
        });

        /**
         * Table: payment_gateway_logs
         * 
         * Table Description:
         * Complete audit of payment gateway communications. Stores raw HTTP interactions
         * for compliance and debugging.
         * 
         * Workflow Role:
         * - Provides API request/response tracing
         * - Enables gateway performance monitoring
         * - Serves as legal evidence for disputes
         * 
         * Key Workflow Processes:
         * - Debugging gateway integration issues
         * - Monitoring API latency
         * - Security incident investigations
         * 
         * Columns:
         * - Relationships: payment_id, transaction_id
         * - Request: direction, url, method, headers
         * - Response: status_code, body, response_time
         * 
         * Note:
         * - Stores raw request bodies including sensitive data
         * - response_time_ms helps identify performance issues
         * - Requires secure access controls
         */
        Schema::create('payment_gateway_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null');
            $table->foreignId('transaction_id')->nullable()->constrained('payment_transactions')->onDelete('set null');

            // Request/response details
            $table->enum('direction', ['request', 'response']);
            $table->string('url', 512);
            $table->string('method', 10);
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->json('headers')->nullable();
            $table->longText('body')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('payment_id');
            $table->index('transaction_id');
            $table->index('created_at');
            $table->index('direction');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_gateway_logs');
        Schema::dropIfExists('payment_errors');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payment_refunds');
        Schema::dropIfExists('vendor_payments');
        Schema::dropIfExists('payments');
    }
};
