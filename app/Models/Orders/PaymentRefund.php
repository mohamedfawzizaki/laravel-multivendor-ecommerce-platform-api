<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentRefund extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSED = 'processed';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'order_payment_id',
        'amount',
        'reason',
        'status',
        'processed_at',
        'transaction_id',
        'gateway_response'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'gateway_response' => 'array'
    ];

    public function payment()
    {
        return $this->belongsTo(OrderPayment::class, 'order_payment_id');
    }
}
