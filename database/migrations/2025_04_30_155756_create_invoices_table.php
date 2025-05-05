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
        /**
         * Table: invoices
         * 
         * Table Description:
         * Central repository for all billing documents in the system. Manages invoice lifecycle
         * from creation to payment, supporting multiple invoice types and international billing.
         * 
         * Workflow Role:
         * - Generates formal billing documents
         * - Tracks payment deadlines and penalties
         * - Manages tax compliance and currency conversions
         * 
         * Key Workflow Processes:
         * - Automated invoice generation from orders
         * - Tax calculation and breakdown
         * - Late fee application and dispute handling
         * 
         * Columns:
         * - Relationships: vendor_id, order_id, vendor_order_id
         * - Identification: invoice_number, type
         * - Addresses: billing/shipping_address (JSON)
         * - Financials: subtotal, tax_amount, total_amount
         * - Payment Terms: due_date, late_fee_percentage
         * - Status: status with index
         * - Currency: currency_code, exchange_rate
         * - Audit: digital_signature, soft deletes
         * 
         * Note:
         * - Supports multiple tax jurisdictions via tax_breakdown
         * - JSON addresses maintain historical billing information
         * - exchange_rate enables multi-currency reconciliation
         */
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('vendor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('vendor_order_id')->constrained('vendor_orders')->onDelete('cascade');

            // Invoice identification
            $table->string('invoice_number')->unique()->comment('Sequential invoice ID');
            $table->enum('type', [
                'platform',
                'vendor',
                'subscription',
                'credit_note',
                'proforma',
                'recurring'
            ])->default('platform')->index();

            // Billing details
            $table->json('billing_address');
            $table->json('shipping_address')->nullable();
            $table->string('tax_id', 50)->nullable()->comment('VAT/GST number');
            $table->string('vat_number', 50)->nullable();

            // Financial details
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_amount', 12, 2);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->json('tax_breakdown')->nullable();
            $table->json('discount_details')->nullable();

            // Payment terms
            $table->enum('payment_terms', [
                'net_7',
                'net_15',
                'net_30',
                'due_on_receipt',
                'custom'
            ])->default('due_on_receipt');
            $table->date('due_date')->nullable();
            $table->decimal('late_fee_percentage', 5, 2)->default(0);

            // Status tracking
            $table->enum('status', [
                'draft',
                'issued',
                'paid',
                'partially_paid',
                'overdue',
                'void',
                'disputed'
            ])->default('draft')->index();

            // Currency information
            $table->string('currency_code', 3)->default('USD');
            $table->foreign('currency_code')->references('code')->on('currencies');
            $table->decimal('exchange_rate', 12, 6)->default(1.0);

            // Audit fields
            $table->foreignUuid('issued_by')->nullable()->constrained('users');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->ipAddress('created_from')->nullable();
            $table->string('digital_signature')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['vendor_id', 'status']);
            $table->index(['order_id', 'type']);
            $table->index('due_date');
        });

        /**
         * Table: invoice_items
         * 
         * Table Description:
         * Line-item details for invoice charges. Maintains historical pricing and tax
         * calculations at time of invoice generation.
         * 
         * Workflow Role:
         * - Breaks down invoice charges
         * - Preserves item-level financial data
         * - Supports partial payments/refunds
         * 
         * Key Workflow Processes:
         * - Automatic calculation of line totals
         * - Tax application per item
         * - Discount type handling
         * 
         * Columns:
         * - Relationships: invoice_id, order_item_id
         * - Item Details: description, sku, quantity
         * - Pricing: unit_price, tax_rate, discount
         * - Calculations: subtotal, tax_amount, total (stored)
         * 
         * Note:
         * - Stored generated columns ensure calculation integrity
         * - Nullable order_item_id allows manual line items
         * - Quantity supports decimal values for services
         */
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('order_item_id')->nullable()->constrained('order_items');

            // Item details
            $table->string('description');
            $table->string('sku')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('discount_rate', 5, 2)->default(0);
            $table->decimal('discount_amount', 5, 2)->default(0);
            $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');

            // Financial breakdown
            $table->decimal('subtotal', 12, 2)->storedAs('quantity * unit_price');
            $table->decimal('tax_amount', 12, 2)->storedAs('(quantity * unit_price) * tax_rate / 100');
            $table->decimal('total', 12, 2)->storedAs('subtotal + tax_amount - discount_amount');

            $table->timestamps();
        });

        /**
         * Table: invoice_taxes
         * 
         * Table Description:
         * Detailed tax breakdown for invoices. Supports multiple tax types and rates
         * per invoice for complex tax jurisdictions.
         * 
         * Workflow Role:
         * - Provides audit-ready tax records
         * - Enables tax type reporting
         * - Manages compound tax calculations
         * 
         * Key Workflow Processes:
         * - Tax rate validation
         * - Tax authority reporting
         * - VAT/GST-specific handling
         * 
         * Columns:
         * - Relationships: invoice_id
         * - Tax Details: tax_name, tax_rate, tax_amount
         * - Classification: tax_type
         * 
         * Note:
         * - Separate from order_taxes for billing flexibility
         * - Supports mixed tax types per invoice
         */
        Schema::create('invoice_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');

            $table->string('tax_name');
            $table->decimal('tax_rate', 5, 2);
            $table->decimal('tax_amount', 12, 2);
            $table->enum('tax_type', [
                'vat',
                'gst',
                'sales_tax',
                'service_tax'
            ])->default('vat');

            $table->timestamps();
        });

        /**
         * Table: invoice_attachments
         * 
         * Table Description:
         * Secures digital assets related to invoices. Maintains document integrity
         * and version control for billing documents.
         * 
         * Workflow Role:
         * - Stores supporting documents
         * - Templates PDF generation
         * - Manages document retention
         * 
         * Key Workflow Processes:
         * - Automated PDF invoice generation
         * - Contract/document attachment
         * - Tamper-evident file storage
         * 
         * Columns:
         * - Relationships: invoice_id, uploaded_by
         * - File Details: type, file_path
         * - Integrity: file_hash (SHA-256)
         * 
         * Note:
         * - file_hash prevents unauthorized modifications
         * - Type classification enables document management
         * - Supports custom attachment types
         */
        Schema::create('invoice_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');

            $table->enum('type', [
                'invoice_pdf',
                'receipt',
                'contract',
                'custom'
            ])->default('invoice_pdf');

            $table->string('file_path');
            $table->string('file_hash')->comment('SHA-256 checksum');
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users');

            $table->timestamps();
        });

        /**
         * Table: invoice_payments
         * 
         * Table Description:
         * Payment application tracking system. Links payments to specific invoices
         * with flexible payment application rules.
         * 
         * Workflow Role:
         * - Manages partial payments
         * - Tracks advance payments
         * - Supports installment plans
         * 
         * Key Workflow Processes:
         * - Payment allocation logic
         * - Installment plan tracking
         * - Payment-invoice reconciliation
         * 
         * Columns:
         * - Relationships: invoice_id, payment_id
         * - Payment: amount, payment_date, type
         * - References: transaction_id, notes
         * 
         * Note:
         * - Allows multiple payments per invoice
         * - payment_type controls accounting treatment
         * - Links to gateway transactions
         */
        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('payment_id')->constrained('payments')->onDelete('cascade');

            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->enum('payment_type', [
                'full',
                'partial',
                'advance',
                'installment'
            ])->default('full');

            $table->string('transaction_id')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });

        /**
         * Table: invoice_reminders
         * 
         * Table Description:
         * Automated payment reminder system. Tracks customer communications
         * and escalation workflows.
         * 
         * Workflow Role:
         * - Manages collections process
         * - Documents customer notifications
         * - Supports legal compliance
         * 
         * Key Workflow Processes:
         * - Scheduled reminder escalation
         * - Multi-channel delivery tracking
         * - Dispute resolution initiation
         * 
         * Columns:
         * - Relationships: invoice_id, sent_by
         * - Reminder: type, sent_at, sent_via
         * 
         * Note:
         * - sent_via tracks delivery method (email/SMS)
         * - Escalates from due to overdue reminders
         * - Timestamps provide legal audit trail
         */
        Schema::create('invoice_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');

            $table->enum('reminder_type', [
                'payment_due',
                'payment_overdue',
                'final_notice'
            ])->default('payment_due');

            $table->timestamp('sent_at');
            $table->string('sent_via')->default('email');
            $table->foreignUuid('sent_by')->nullable()->constrained('users');

            $table->timestamps();
        });

        /**
         * Table: invoice_ledger
         * 
         * Table Description:
         * Double-entry accounting ledger for invoice transactions. Maintains
         * complete financial audit trail.
         * 
         * Workflow Role:
         * - Ensures accounting integrity
         * - Tracks manual adjustments
         * - Supports financial reporting
         * 
         * Key Workflow Processes:
         * - General ledger synchronization
         * - Account reconciliation
         * - Financial period closing
         * 
         * Columns:
         * - Relationships: invoice_id, recorded_by
         * - Entry: type, amount, description
         * 
         * Note:
         * - entry_type follows accounting standards
         * - Requires matching credit/debit entries
         * - description mandates audit clarity
         */
        Schema::create('invoice_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');

            $table->enum('entry_type', [
                'debit',
                'credit',
                'adjustment'
            ])->default('debit');

            $table->decimal('amount', 12, 2);
            $table->string('description');
            $table->foreignUuid('recorded_by')->nullable()->constrained('users');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_ledger');
        Schema::dropIfExists('invoice_reminders');
        Schema::dropIfExists('invoice_payments');
        Schema::dropIfExists('invoice_attachments');
        Schema::dropIfExists('invoice_taxes');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};