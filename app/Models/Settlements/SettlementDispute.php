<?php

namespace App\Models\Settlements;

use App\Models\User;
use App\Models\Settlements\Settlement;
use Illuminate\Database\Eloquent\Model;

class SettlementDispute extends Model
{
    protected $fillable = [
        'settlement_id',
        'raised_by',
        'reason',
        'status',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function settlement()
    {
        return $this->belongsTo(Settlement::class);
    }

    public function raisedBy()
    {
        return $this->belongsTo(User::class, 'raised_by');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}