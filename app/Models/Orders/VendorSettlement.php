<?php

namespace App\Models\Orders;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VendorSettlement extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSED = 'processed';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'vendor_id',
        'order_payment_id',
        'amount',
        'currency_code',
        'method',
        'status',
        'processed_at',
        'transaction_id',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime'
    ];

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function payment()
    {
        return $this->belongsTo(OrderPayment::class, 'order_payment_id');
    }

    public function markAsProcessed($transactionId = null)
    {
        $this->update([
            'status' => self::STATUS_PROCESSED,
            'transaction_id' => $transactionId,
            'processed_at' => now()
        ]);
    }
}