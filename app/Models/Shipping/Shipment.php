<?php

namespace App\Models\Shipping;

use App\Models\User;
use Illuminate\Support\Str;
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

    protected $fillable = [
        'user_id',
        'order_id',
        'vendor_id', 
        'vendor_order_id',
        'carrier_id',
        'shipping_address_id',
        'tracking_number',
        'shipping_cost',
        'insurance_cost',
        'package_weight',
        'service_level',
        'status',
        'estimated_delivery_date',
        'out_for_delivery_at',
        'shipped_at'
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


    public static function generateUniqueTrackingNumber()
    {
        return 'STUN-' . date('Ymd') . '-' . strtoupper(Str::random(6));
    }

}