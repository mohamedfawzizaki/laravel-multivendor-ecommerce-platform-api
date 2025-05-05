<?php

namespace App\Models\Payments;

use App\Models\User;
use App\Models\Orders\Order;
use App\Models\Payments\OrderPayment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentError extends Model
{
    protected $fillable = [
        'payment_id',
        'user_id',
        'order_id',
        'error_type',
        'error_code',
        'error_message',
        'gateway_response',
        'is_recoverable',
        'retry_count',
    ];

    protected $casts = [
        'gateway_response' => 'array',
        'is_recoverable' => 'boolean',
        'retry_count' => 'integer',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(OrderPayment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}