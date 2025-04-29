<?php

namespace App\Models\Shipping;

use App\Models\User;
use App\Models\Orders\Order;
use App\Enums\ShipmentStatus;
use App\Models\Shipping\ShipmentEvent;
use Illuminate\Database\Eloquent\Model;
use App\Models\Shipping\ShippingAddress;
use App\Models\Shipping\ShippingCarrier;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Shipment extends Model
{
    use HasFactory, SoftDeletes;

    // Status constants
    const STATUS_LABEL_CREATED = 'label_created';
    const STATUS_PENDING = 'pending';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_EXCEPTION = 'exception';
    const STATUS_RETURNED = 'returned';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'vendor_order_id',
        'carrier_id',
        'shipping_address_id',
        'tracking_number',
        'shipping_cost',
        'insurance_cost',
        'package_weight',
        'service_level',
        'status',
        'estimated_delivery_date'
    ];

    protected $casts = [
        'shipping_cost' => 'decimal:2',
        'insurance_cost' => 'decimal:2',
        'package_weight' => 'decimal:3',
        'estimated_delivery_date' => 'date',
        'label_created_at' => 'datetime',
        'shipped_at' => 'datetime',
        'out_for_delivery_at' => 'datetime',
        'delivered_at' => 'datetime',
        'last_tracking_update_at' => 'datetime',

        'status' => ShipmentStatus::class,

    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function carrier()
    {
        return $this->belongsTo(ShippingCarrier::class);
    }

    public function shippingAddress()
    {
        return $this->belongsTo(ShippingAddress::class);
    }

    public function events()
    {
        return $this->hasMany(ShipmentEvent::class)->orderBy('occurred_at', 'desc');
    }

    // Scopes
    public function scopeDelivered($query)
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }

    public function scopeInTransit($query)
    {
        return $query->where('status', self::STATUS_IN_TRANSIT);
    }

    // Status helpers
    public function markAsLabelCreated()
    {
        $this->update([
            'status' => self::STATUS_LABEL_CREATED,
            'label_created_at' => now()
        ]);
    }

    public function markAsInTransit()
    {
        $this->update([
            'status' => self::STATUS_IN_TRANSIT,
            'shipped_at' => now(),
            'last_tracking_update_at' => now()
        ]);
    }

    public function markAsDelivered()
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
            'last_tracking_update_at' => now()
        ]);
    }

    // Helpers
    public function getTrackingUrlAttribute()
    {
        return $this->carrier->getTrackingUrl($this->tracking_number);
    }

    public function getTotalShippingCostAttribute()
    {
        return $this->shipping_cost + $this->insurance_cost;
    }

    public function addEvent($status, $description = null, $location = null)
    {
        return $this->events()->create([
            'status' => $status,
            'description' => $description,
            'location' => $location,
            'occurred_at' => now()
        ]);
    }

    public function updateFromCarrier($status, $eventData = [])
    {
        $this->update([
            'status' => $status,
            'last_tracking_update_at' => now()
        ]);

        $this->addEvent($status, $eventData['description'] ?? null, $eventData['location'] ?? null);

        // Update timestamps based on status
        switch ($status) {
            case self::STATUS_OUT_FOR_DELIVERY:
                $this->update(['out_for_delivery_at' => now()]);
                break;
            case self::STATUS_DELIVERED:
                $this->markAsDelivered();
                break;
        }
    }
}