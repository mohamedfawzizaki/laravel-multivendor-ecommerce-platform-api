<?php

namespace App\Models\Payments;

use App\Models\User;
use App\Models\Orders\Order;
use App\Models\Orders\VendorOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    // Payment status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_AUTHORIZED = 'authorized';
    public const STATUS_CAPTURED = 'captured';
    public const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';
    public const STATUS_FULLY_REFUNDED = 'fully_refunded';
    public const STATUS_FAILED = 'failed';
    public const STATUS_VOIDED = 'voided';
    public const STATUS_DISPUTED = 'disputed';
    public const STATUS_CHARGEBACK = 'chargeback';

    // Payment method constants
    public const METHOD_CREDIT_CARD = 'credit_card';
    public const METHOD_DEBIT_CARD = 'debit_card';
    public const METHOD_PAYPAL = 'paypal';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';
    public const METHOD_DIGITAL_WALLET = 'digital_wallet';
    public const METHOD_CRYPTO = 'crypto';
    public const METHOD_COD = 'cash_on_delivery';
    public const METHOD_INSTALLMENT = 'installment';

    protected $fillable = [
        'user_id',
        'order_id',
        'vendor_order_id',
        'amount',
        'currency',
        'base_amount',
        'exchange_rate',
        'payment_status',
        'payment_method',
        'payment_method_details',
        'payment_gateway',
        'gateway_reference',
        'fraud_score',
        'captured_at',
        'refunded_at',
        'failed_at'
    ];

    protected $casts = [
        'amount' => 'decimal:12',
        'base_amount' => 'decimal:12',
        'exchange_rate' => 'decimal:6',
        'payment_method_details' => 'array',
        'fraud_score' => 'integer',
        'captured_at' => 'datetime',
        'refunded_at' => 'datetime',
        'failed_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function vendorOrder(): BelongsTo
    {
        return $this->belongsTo(VendorOrder::class);
    }

    public function markAsAuthorized(string $gatewayReference): bool
    {
        return $this->update([
            'payment_status' => self::STATUS_AUTHORIZED,
            'gateway_reference' => $gatewayReference
        ]);
    }

    public function markAsCaptured(): bool
    {
        return $this->update([
            'payment_status' => self::STATUS_CAPTURED,
            'captured_at' => now()
        ]);
    }

    public function markAsFailed(string $reason): bool
    {
        return $this->update([
            'payment_status' => self::STATUS_FAILED,
            'failed_at' => now()
        ]);
    }

    public function isCaptured(): bool
    {
        return $this->payment_status === self::STATUS_CAPTURED;
    }

    public function getFormattedAmountAttribute()//: string
    {
        // return currency_format($this->amount, $this->currency);
    }
}