<?php

namespace App\Models\Vendors;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Models\Vendors\VendorPaymentAccount;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VendorPaymentVerification extends Model
{
    use HasFactory;

    // Document types
    public const DOC_BANK_STATEMENT = 'bank_statement';
    public const DOC_ID_PROOF = 'id_proof';
    public const DOC_TAX = 'tax_document';
    public const DOC_ADDRESS = 'address_proof';
    public const DOC_BUSINESS_LICENSE = 'business_license';
    public const DOC_OWNERSHIP = 'ownership_proof';
    public const DOC_POA = 'poa';

    // Document statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'payment_account_id',
        'document_type',
        'document_status',
        'document_path',
        'document_checksum',
        'expires_at',
        'latitude',
        'longitude',
        'verification_note',
        'reviewed_by',
        'risk_indicators'
    ];

    protected $casts = [
        'expires_at' => 'date',
        'risk_indicators' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8'
    ];

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(VendorPaymentAccount::class, 'payment_account_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approve(User $reviewer, string $note = null): bool
    {
        return $this->update([
            'document_status' => self::STATUS_APPROVED,
            'reviewed_by' => $reviewer->id,
            'verification_note' => $note
        ]);
    }

    public function reject(User $reviewer, string $note): bool
    {
        return $this->update([
            'document_status' => self::STATUS_REJECTED,
            'reviewed_by' => $reviewer->id,
            'verification_note' => $note
        ]);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getLocationAttribute(): ?string
    {
        return $this->latitude && $this->longitude 
            ? "{$this->latitude},{$this->longitude}"
            : null;
    }
}