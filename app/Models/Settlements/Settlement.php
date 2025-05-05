<?php

namespace App\Models\Settlements;

use App\Models\User;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Model;
use App\Models\Settlements\SettlementItem;
use App\Models\Settlements\SettlementDispute;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Settlements\SettlementAdjustment;

class Settlement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'settlement_uuid',
        'vendor_id',
        'start_date',
        'end_date',
        'payout_date',
        'total_sales',
        'total_refunds',
        'total_commission',
        'total_fees',
        'net_amount',
        'currency_code',
        'exchange_rate',
        'payout_method',
        'payout_reference',
        'status',
        'processed_by',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'payout_date' => 'date',
        'exchange_rate' => 'decimal:6',
        'total_sales' => 'decimal:2',
        'total_refunds' => 'decimal:2',
        'total_commission' => 'decimal:2',
        'total_fees' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    // Relationships
    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function items()
    {
        return $this->hasMany(SettlementItem::class);
    }

    public function adjustments()
    {
        return $this->hasMany(SettlementAdjustment::class);
    }

    public function disputes()
    {
        return $this->hasMany(SettlementDispute::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }
}