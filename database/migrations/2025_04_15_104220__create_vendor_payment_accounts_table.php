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
        Schema::create('vendor_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('vendor_id')->constrained('users')->onDelete('cascade');
            $table->decimal('available_balance', 12, 2)->default(0);
            $table->decimal('pending_balance', 12, 2)->default(0);
            $table->timestamps();
        });

        /**
         * 
         * Table: vendor_payment_accounts
         * 
         * Table Description:
         * Secure repository for vendor payment integrations and compliance tracking.
         * Manages payment provider configurations with enterprise-grade security.
         * 
         * Workflow Role:
         * - Stores encrypted payment provider credentials
         * - Manages KYC/AML compliance lifecycle
         * - Controls payout scheduling and limits
         * 
         * Key Workflow Processes:
         * - Payment account enrollment and verification
         * - Automated payout execution
         * - Compliance monitoring and restrictions
         * 
         * Columns:
         * - Relationships: vendor_id, verified_by
         * - Provider: provider, external_account_id
         * - Security: account_details (encrypted), api_credentials, encryption_version
         * - Compliance: verification_status, kyc_status, aml_status, tax_identifier
         * - Payout: supported_currencies, min/max_payout_amount, payout_schedule
         * - Integration: webhook_endpoint, last_synced_at, provider_response
         * - Audit: created_from, user_agent, soft deletes
         * 
         * Note:
         * - account_details uses AES-256-GCM encryption
         * - External_account_id links to payment provider records
         * - Cumulative_payout_limit triggers account reviews
         */
        Schema::create('vendor_payment_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('vendor_id')->constrained('users')->onDelete('cascade');

            // Payment Provider Configuration
            $table->enum('provider', [
                'paypal',
                'stripe_connect',
                'bank_transfer',
                'wise',
                'payoneer',
                'vendor_balance',
                'custom'
            ])->index();

            $table->decimal('available_balance', 12, 2)->default(0);
            $table->decimal('pending_balance', 12, 2)->default(0);

            // Encrypted Account Details
            $table->json('account_details')->comment('AES-256-GCM encrypted payment details');
            $table->string('encryption_version', 20)->default('v1');
            $table->json('api_credentials')->nullable()->comment('Encrypted OAuth tokens/keys');

            // Compliance & Verification
            $table->enum('verification_status', [
                'unverified',
                'pending',
                'verified',
                'suspended',
                'expired',
                'under_review'
            ])->default('unverified')->index();

            $table->enum('kyc_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->enum('aml_status', ['clear', 'flagged', 'restricted'])->default('clear');
            $table->string('tax_identifier', 50)->nullable()->comment('VAT/GST number');

            // Payout Configuration
            $table->json('supported_currencies')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->decimal('min_payout_amount', 10, 2)->default(100.00);
            $table->decimal('max_payout_amount', 10, 2)->nullable();
            $table->decimal('cumulative_payout_limit', 12, 2)->nullable();
            $table->enum('payout_schedule', [
                'daily',
                'weekly',
                'bi-weekly',
                'monthly',
                'manual'
            ])->default('weekly');
            $table->enum('payout_priority', ['primary', 'secondary', 'emergency'])->default('primary');

            // Provider Integration
            $table->string('external_account_id')->nullable()->comment('Payment provider reference ID');
            $table->json('provider_response')->nullable()->comment('Raw API response');
            $table->string('webhook_endpoint')->nullable();
            $table->timestamp('last_synced_at')->nullable();

            // Security Features
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_2fa')->default(false);
            $table->unsignedSmallInteger('failed_attempts')->default(0);
            $table->string('last_failure_reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_used_at')->nullable();

            // Audit & Tracking
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->ipAddress('created_from')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->unique(['vendor_id', 'external_account_id']);
            $table->index(['provider', 'verification_status']);
            $table->index(['kyc_status', 'aml_status']);
            $table->index('expires_at');
        });

        /**
         * Table: vendor_payment_verifications
         * 
         * Table Description:
         * Centralized document verification system for payment account compliance.
         * Maintains audit trail of KYC/AML documentation with geolocation context.
         * 
         * Workflow Role:
         * - Manages document verification workflows
         * - Tracks document expiration dates
         * - Stores document integrity checks
         * 
         * Key Workflow Processes:
         * - Automated document checksum validation
         * - Geospatial fraud pattern detection
         * - Multi-stage verification approvals
         * 
         * Columns:
         * - Relationships: payment_account_id, reviewed_by
         * - Documents: document_type, document_path, checksum
         * - Status: document_status, expires_at
         * - Geolocation: latitude, longitude
         * - Risk: risk_indicators, verification_note
         * 
         * Note:
         * - document_checksum prevents tampering
         * - Spatial index enables geographic analysis
         * - Expired documents trigger re-verification
         */
        Schema::create('vendor_payment_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_account_id')->constrained('vendor_payment_accounts');

            // Document Details
            $table->enum('document_type', [
                'bank_statement',
                'id_proof',
                'tax_document',
                'address_proof',
                'business_license',
                'ownership_proof',
                'poa'
            ])->index();

            $table->enum('document_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('document_path');
            $table->string('document_checksum')->comment('SHA-256 hash');
            $table->date('expires_at')->nullable();

            // Geolocation Context
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Verification Metadata
            $table->string('verification_note')->nullable();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users');
            $table->json('risk_indicators')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['document_type', 'document_status']);
        });

        /**
         * Table: payment_account_audits
         * 
         * Table Description:
         * Immutable audit trail of payment account modifications and security events.
         * Enables forensic analysis and regulatory compliance reporting.
         * 
         * Workflow Role:
         * - Tracks all account lifecycle changes
         * - Monitors suspicious activity patterns
         * - Supports dispute resolution evidence
         * 
         * Key Workflow Processes:
         * - Real-time security event logging
         * - Risk scoring automation
         * - Change impact analysis
         * 
         * Columns:
         * - Relationships: payment_account_id, performed_by
         * - Audit: event_type, old/new_values
         * - Security: ip_address, user_agent, geo_location
         * - Risk: risk_score, risk_indicators
         * 
         * Note:
         * - old/new_values track configuration changes
         * - risk_score enables automated alerts
         * - Geo_location derived from IP analysis
         */
        Schema::create('payment_account_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_account_id')->constrained('vendor_payment_accounts');

            // Audit Details
            $table->enum('event_type', [
                'created',
                'updated',
                'verified',
                'suspended',
                'deleted',
                'failed_verification',
                'payout_attempt',
                'security_alert'
            ])->index();

            // Change Tracking
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Security Context
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('geo_location')->nullable();

            // Risk Assessment
            $table->decimal('risk_score', 5, 2)->nullable();
            $table->json('risk_indicators')->nullable();

            // Actor Tracking
            $table->foreignUuid('performed_by')->nullable()->constrained('users');

            $table->timestamps();

            // Indexes
            $table->index(['event_type', 'created_at']);
            $table->index('risk_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_wallets');
        Schema::dropIfExists('payment_account_audits');
        Schema::dropIfExists('vendor_payment_verifications');
        Schema::dropIfExists('vendor_payment_accounts');
    }
};
