<?php

namespace App\Models\Vendors;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Model;
use App\Models\Vendors\PaymentAccountAudit;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Vendors\VendorPaymentVerification;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VendorPaymentAccount extends Model
{
    use HasFactory, SoftDeletes;

    // Payment providers
    public const PROVIDER_PAYPAL = 'paypal';
    public const PROVIDER_STRIPE = 'stripe_connect';
    public const PROVIDER_BANK = 'bank_transfer';
    public const PROVIDER_WISE = 'wise';
    public const PROVIDER_PAYONEER = 'payoneer';
    public const PROVIDER_BALANCE = 'vendor_balance';
    public const PROVIDER_CUSTOM = 'custom';

    // Verification statuses
    public const VERIFICATION_UNVERIFIED = 'unverified';
    public const VERIFICATION_PENDING = 'pending';
    public const VERIFICATION_VERIFIED = 'verified';
    public const VERIFICATION_SUSPENDED = 'suspended';
    public const VERIFICATION_EXPIRED = 'expired';
    public const VERIFICATION_UNDER_REVIEW = 'under_review';

    // KYC statuses
    public const KYC_PENDING = 'pending';
    public const KYC_VERIFIED = 'verified';
    public const KYC_REJECTED = 'rejected';

    // AML statuses
    public const AML_CLEAR = 'clear';
    public const AML_FLAGGED = 'flagged';
    public const AML_RESTRICTED = 'restricted';

    // Payout schedules
    public const SCHEDULE_DAILY = 'daily';
    public const SCHEDULE_WEEKLY = 'weekly';
    public const SCHEDULE_BIWEEKLY = 'bi-weekly';
    public const SCHEDULE_MONTHLY = 'monthly';
    public const SCHEDULE_MANUAL = 'manual';

    protected $fillable = [
        'vendor_id',
        'provider',
        'account_details',
        'encryption_version',
        'api_credentials',
        'verification_status',
        'kyc_status',
        'aml_status',
        'tax_identifier',
        'supported_currencies',
        'currency',
        'min_payout_amount',
        'max_payout_amount',
        'cumulative_payout_limit',
        'payout_schedule',
        'payout_priority',
        'external_account_id',
        'provider_response',
        'webhook_endpoint',
        'last_synced_at',
        'is_primary',
        'is_active',
        'requires_2fa',
        'failed_attempts',
        'last_failure_reason',
        'expires_at',
        'verified_at',
        'last_used_at',
        'verified_by',
        'created_from',
        'user_agent'
    ];

    protected $casts = [
        'account_details' => 'encrypted:array',
        'api_credentials' => 'encrypted:array',
        'supported_currencies' => 'array',
        'provider_response' => 'array',
        'min_payout_amount' => 'decimal:10',
        'max_payout_amount' => 'decimal:10',
        'cumulative_payout_limit' => 'decimal:12',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'requires_2fa' => 'boolean',
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'last_used_at' => 'datetime',
        'last_synced_at' => 'datetime'
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(VendorPaymentVerification::class, 'payment_account_id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(PaymentAccountAudit::class, 'payment_account_id');
    }

    public function markAsVerified(User $verifier): bool
    {
        return $this->update([
            'verification_status' => self::VERIFICATION_VERIFIED,
            'verified_by' => $verifier->id,
            'verified_at' => now()
        ]);
    }

    public function isVerified(): bool
    {
        return $this->verification_status === self::VERIFICATION_VERIFIED;
    }

    public function supportsCurrency(string $currency): bool
    {
        return in_array($currency, $this->supported_currencies ?? [$this->currency]);
    }

    public function getDecryptedAccountDetails(): array
    {
        return $this->account_details;
    }

    public function recordFailedAttempt(string $reason): void
    {
        $this->update([
            'failed_attempts' => $this->failed_attempts + 1,
            'last_failure_reason' => $reason
        ]);
    }
}