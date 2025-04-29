<?php

namespace App\Models\Orders;

use App\Models\User;
use App\Models\Currency;
use App\Models\Orders\Order;
use Illuminate\Support\Facades\DB;
use App\Models\Orders\PaymentRefund;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderPayment extends Model
{
    use HasFactory;

    // Payment methods
    const METHOD_CREDIT_CARD = 'credit_card';
    const METHOD_PAYPAL = 'paypal';
    const METHOD_STRIPE = 'stripe';
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_CASH = 'cash';
    const METHOD_VENDOR_CREDIT = 'vendor_credit';
    const METHOD_SPLIT_PAYMENT = 'split_payment';

    // Payment statuses
    const STATUS_PENDING = 'pending';
    const STATUS_AUTHORIZED = 'authorized';
    const STATUS_PAID = 'paid';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';
    const STATUS_SPLIT_PENDING = 'split_pending';
    const STATUS_SETTLEMENT_PENDING = 'settlement_pending';

    // Payment types
    const TYPE_PARENT = 'parent';
    const TYPE_CHILD = 'child';
    const TYPE_STANDALONE = 'standalone';

    protected $fillable = [
        'order_id',
        'vendor_id',
        'parent_payment_id',
        'method',
        'status',
        'payment_type',
        'transaction_id',
        'amount',
        'vendor_amount',
        'platform_fee',
        'currency_code',
        'is_split_payment',
        'split_details',
        'processed_at',
        'gateway_response',
        'vendor_settlement_details'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'vendor_amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'processed_at' => 'datetime',
        'gateway_response' => 'array',
        'vendor_settlement_details' => 'array',
        'split_details' => 'array',
        'is_split_payment' => 'boolean'
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function refunds()
    {
        return $this->hasMany(PaymentRefund::class);
    }

    public function parentPayment()
    {
        return $this->belongsTo(self::class, 'parent_payment_id');
    }

    public function childPayments()
    {
        return $this->hasMany(self::class, 'parent_payment_id');
    }

    // Scopes
    public function scopeForVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeSuccessful($query)
    {
        return $query->whereIn('status', [self::STATUS_PAID, self::STATUS_AUTHORIZED]);
    }

    public function scopeNeedsSettlement($query)
    {
        return $query->where('status', self::STATUS_PAID)
            ->whereDoesntHave('settlements');
    }

    public function scopeParentPayments($query)
    {
        return $query->where('payment_type', self::TYPE_PARENT);
    }

    public function scopeChildPayments($query)
    {
        return $query->where('payment_type', self::TYPE_CHILD);
    }

    public function scopeStandalonePayments($query)
    {
        return $query->where('payment_type', self::TYPE_STANDALONE);
    }

    // Methods
    public function isSplitPayment(): bool
    {
        return $this->is_split_payment || $this->method === self::METHOD_SPLIT_PAYMENT;
    }

    public function isParentPayment(): bool
    {
        return $this->payment_type === self::TYPE_PARENT;
    }

    public function isChildPayment(): bool
    {
        return $this->payment_type === self::TYPE_CHILD;
    }

    public function isStandalonePayment(): bool
    {
        return $this->payment_type === self::TYPE_STANDALONE;
    }

    public function calculateVendorAmount(): float
    {
        if ($this->vendor_amount !== null) {
            return $this->vendor_amount;
        }

        if ($this->isSplitPayment()) {
            $commissionRate = $this->order->vendor->commission_rate ?? config('vendors.default_commission', 0.15);
            return $this->amount * (1 - $commissionRate);
        }

        return $this->amount;
    }

    public function calculatePlatformFee(): float
    {
        if ($this->platform_fee !== null) {
            return $this->platform_fee;
        }

        if ($this->isSplitPayment()) {
            return $this->amount - $this->calculateVendorAmount();
        }

        return 0;
    }

    public function markAsPaid(?string $transactionId = null): bool
    {
        return $this->update([
            'status' => self::STATUS_PAID,
            'transaction_id' => $transactionId ?? $this->transaction_id,
            'vendor_amount' => $this->calculateVendorAmount(),
            'platform_fee' => $this->calculatePlatformFee(),
            'processed_at' => now()
        ]);
    }

    public function createRefund(float $amount, string $reason): PaymentRefund
    {
        return DB::transaction(function () use ($amount, $reason) {
            $refund = $this->refunds()->create([
                'amount' => $amount,
                'reason' => $reason,
                'status' => PaymentRefund::STATUS_PENDING
            ]);

            $this->updateStatusAfterRefund($amount);

            return $refund;
        });
    }

    protected function updateStatusAfterRefund(float $amount): void
    {
        $newStatus = $amount < $this->amount 
            ? self::STATUS_PARTIALLY_REFUNDED 
            : self::STATUS_REFUNDED;

        $this->update(['status' => $newStatus]);
    }

    public function createChildPayment(array $attributes): self
    {
        if (!$this->isParentPayment()) {
            throw new \LogicException('Only parent payments can create child payments');
        }

        return DB::transaction(function () use ($attributes) {
            $childPayment = $this->childPayments()->create(array_merge($attributes, [
                'payment_type' => self::TYPE_CHILD,
                'is_split_payment' => true,
                'status' => self::STATUS_PENDING
            ]));

            $this->updateSplitDetails();

            return $childPayment;
        });
    }

    protected function updateSplitDetails(): void
    {
        $childCount = $this->childPayments()->count();
        $totalAmount = $this->childPayments()->sum('amount');

        $this->update([
            'split_details' => [
                'child_count' => $childCount,
                'total_amount' => $totalAmount,
                'last_updated' => now()->toDateTimeString()
            ]
        ]);
    }
}