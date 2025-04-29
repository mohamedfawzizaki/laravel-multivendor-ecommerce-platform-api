<?php

namespace App\Models\Shipping;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'status',
        'description',
        'location',
        'occurred_at'
    ];

    protected $casts = [
        'occurred_at' => 'datetime'
    ];

    // Relationships
    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    // Scopes
    public function scopeRecent($query)
    {
        return $query->orderBy('occurred_at', 'desc');
    }

    // Helpers
    public function getFormattedOccurredAtAttribute()
    {
        return $this->occurred_at->format('M j, Y g:i A');
    }
}