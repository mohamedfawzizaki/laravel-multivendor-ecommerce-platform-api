<?php

namespace App\Models\Settlements;

use App\Models\User;
use App\Models\Settlements\Settlement;
use Illuminate\Database\Eloquent\Model;

class SettlementAdjustment extends Model
{
    protected $fillable = [
        'settlement_id',
        'amount',
        'type',
        'description',
        'adjusted_by',
        'reference_id',
        'reference_type',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function settlement()
    {
        return $this->belongsTo(Settlement::class);
    }

    public function adjustedBy()
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }
}