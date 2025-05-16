<?php

namespace App\Models\Payments;

use App\Models\Currency;
use App\Models\Orders\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    // Payment status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_AUTHORIZED = 'authorized';
    public const STATUS_CAPTURED = 'captured';
    public const STATUS_PAID = 'paid';
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
    public const METHOD_CASH_ON_DELIVERY = 'cash_on_delivery';
    public const METHOD_INSTALLMENT = 'installment';

    protected $table = 'payments';

    protected $fillable = [
        'order_id',
        'amount',
        'currency_code',
        'payment_status',
        'payment_method',
        'payment_gateway',
        'transaction_id',
        'gateway_reference',
        'payment_method_details',
        'fraud_score',
        'paid_at',
        'captured_at',
        'refunded_at',
        'failed_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'captured_at' => 'datetime',
        'refunded_at' => 'datetime',
        'failed_at' => 'datetime',
        'payment_method_details' => 'array',
    ];

    protected $dates = [
        'paid_at',
        'captured_at',
        'refunded_at',
        'failed_at',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * Relationships
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    /**
     * Get available payment status options
     */
    public static function getPaymentStatusOptions(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_AUTHORIZED,
            self::STATUS_CAPTURED,
            self::STATUS_PAID,
            self::STATUS_PARTIALLY_REFUNDED,
            self::STATUS_FULLY_REFUNDED,
            self::STATUS_FAILED,
            self::STATUS_VOIDED,
            self::STATUS_DISPUTED,
            self::STATUS_CHARGEBACK,
        ];
    }

    /**
     * Get available payment method options
     */
    public static function getPaymentMethodOptions(): array
    {
        return [
            self::METHOD_CREDIT_CARD,
            self::METHOD_DEBIT_CARD,
            self::METHOD_PAYPAL,
            self::METHOD_BANK_TRANSFER,
            self::METHOD_DIGITAL_WALLET,
            self::METHOD_CRYPTO,
            self::METHOD_CASH_ON_DELIVERY,
            self::METHOD_INSTALLMENT,
        ];
    }
}