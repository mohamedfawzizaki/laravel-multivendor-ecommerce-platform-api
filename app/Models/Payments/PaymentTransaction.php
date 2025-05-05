<?php

namespace App\Models\Payments;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'payment_id',
        'parent_transaction_id',
        'transaction_type',
        'amount',
        'currency_code',
        'gateway_transaction_id',
        'gateway_status',
        'gateway_response_code',
        'gateway_response',
        'is_success',
        'requires_action',
        'action_url',
        'is_test',
        'metadata',
        'processed_by',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'is_success' => 'boolean',
        'requires_action' => 'boolean',
        'is_test' => 'boolean',
        'metadata' => 'array',
        'amount' => 'decimal:2',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(OrderPayment::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_transaction_id');
    }

    public function childTransactions()
    {
        return $this->hasMany(self::class, 'parent_transaction_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function gatewayLogs()
    {
        return $this->hasMany(PaymentGatewayLog::class, 'transaction_id');
    }
}