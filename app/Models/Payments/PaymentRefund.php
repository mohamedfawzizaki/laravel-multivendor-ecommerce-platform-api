<?php

namespace App\Models\Payments;

use App\Models\User;
use App\Models\Currency;
use App\Models\Orders\Order;
use App\Models\Payments\OrderPayment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Notifications\RefundProcessedNotification;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentRefund extends Model
{
    use HasFactory, SoftDeletes;

    // Refund status constants
    public const STATUS_REQUESTED = 'requested';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_PARTIALLY_COMPLETED = 'partially_completed';

    // Refund method constants
    public const METHOD_ORIGINAL = 'original';
    public const METHOD_CREDIT = 'credit';
    public const METHOD_MANUAL_REFUND = 'manual_refund';
    public const METHOD_OTHER = 'other';

    protected $fillable = [
        'order_payment_id',
        'processed_by',
        'vendor_id',
        'order_id',
        'amount',
        'currency_code',
        'status',
        'method',
        'refund_reason',
        'customer_notes',
        'internal_notes',
        'transaction_id',
        'rma_number',
        'processed_at',
        'estimated_refund_date',
        'customer_notified_at',
        'gateway_response',
        'items_refunded'
    ];

    protected $casts = [
        'amount' => 'decimal:12',
        'processed_at' => 'datetime',
        'estimated_refund_date' => 'datetime',
        'customer_notified_at' => 'datetime',
        'gateway_response' => 'array',
        'items_refunded' => 'array'
    ];

    public function orderPayment(): BelongsTo
    {
        return $this->belongsTo(OrderPayment::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function markAsCompleted(string $transactionId): bool
    {
        return $this->update([
            'status' => self::STATUS_COMPLETED,
            'transaction_id' => $transactionId,
            'processed_at' => now()
        ]);
    }

    public function isFullRefund(): bool
    {
        return $this->amount === $this->orderPayment->amount;
    }

    // public function getFormattedAmountAttribute(): string
    // {
    //     return currency_format($this->amount, $this->currency_code);
    // }

    public function notifyCustomer(): void
    {
        if (!$this->customer_notified_at) {
            $this->order->user->notify(new RefundProcessedNotification($this));
            $this->update(['customer_notified_at' => now()]);
        }
    }
}