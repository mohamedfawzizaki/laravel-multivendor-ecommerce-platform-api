<?php

namespace App\Models\Settlements;

use App\Models\Payments\OrderPayment;
use App\Models\Settlements\Settlement;
use Illuminate\Database\Eloquent\Model;

class SettlementItem extends Model
{
    protected $fillable = [
        'settlement_id',
        'order_payment_id',
        'gross_amount',
        'commission',
        'fees',
        'net_amount',
        'type',
        'order_id',
        'transaction_id',
        'transaction_date',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'commission' => 'decimal:2',
        'fees' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function settlement()
    {
        return $this->belongsTo(Settlement::class);
    }

    public function orderPayment()
    {
        return $this->belongsTo(OrderPayment::class);
    }
}