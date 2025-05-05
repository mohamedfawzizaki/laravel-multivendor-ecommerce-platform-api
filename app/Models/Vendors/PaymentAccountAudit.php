<?php

namespace App\Models\Vendors;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Models\Vendors\VendorPaymentAccount;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentAccountAudit extends Model
{
    use HasFactory;

    // Event types
    public const EVENT_CREATED = 'created';
    public const EVENT_UPDATED = 'updated';
    public const EVENT_VERIFIED = 'verified';
    public const EVENT_SUSPENDED = 'suspended';
    public const EVENT_DELETED = 'deleted';
    public const EVENT_FAILED_VERIFICATION = 'failed_verification';
    public const EVENT_PAYOUT_ATTEMPT = 'payout_attempt';
    public const EVENT_SECURITY_ALERT = 'security_alert';

    protected $fillable = [
        'payment_account_id',
        'event_type',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'geo_location',
        'risk_score',
        'risk_indicators',
        'performed_by'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'risk_indicators' => 'array'
    ];

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(VendorPaymentAccount::class, 'payment_account_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function isHighRisk(): bool
    {
        return $this->risk_score > 70;
    }

    public function getChangesSummary(): array
    {
        if ($this->event_type === self::EVENT_CREATED) {
            return ['created' => true];
        }

        $changes = [];
        foreach ($this->new_values as $key => $value) {
            if (!array_key_exists($key, $this->old_values ?? []) || $this->old_values[$key] !== $value) {
                $changes[$key] = [
                    'from' => $this->old_values[$key] ?? null,
                    'to' => $value
                ];
            }
        }

        return $changes;
    }
}