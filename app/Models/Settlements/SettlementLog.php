<?php

namespace App\Models\Settlements;

use App\Models\User;
use App\Models\Settlements\Settlement;
use Illuminate\Database\Eloquent\Model;

class SettlementLog extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'settlement_id',
        'event_type',
        'description',
        'metadata',
        'performed_by',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // Relationships
    public function settlement()
    {
        return $this->belongsTo(Settlement::class);
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}