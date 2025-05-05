<?php

namespace App\Models\Orders;

use App\Models\User;
use App\Enums\OrderStatus;
use Illuminate\Support\Str;
use App\Models\Orders\OrderItem;
use App\Models\Shipping\Shipment;
use App\Models\Orders\OrderPayment;
use Illuminate\Database\Eloquent\Model;
use App\Models\Shipping\ShippingAddress;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'order_number',
        'subtotal',
        'tax',
        'total_price',
        'status'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total_price' => 'decimal:2',
        'processed_at' => 'datetime',
        'status' => OrderStatus::class,
    ];

    // protected static function boot()
    // {
    //     parent::boot();

    //     static::creating(function ($model) {
    //         $model->order_number = 'ORD-' . Str::upper(Str::random(6)) . '-' . time();
    //     });
    // }

    public static function generateOrderNumber()
    {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(6));
    }

    protected $with = ['orderItems', 'vendorOrders', 'taxes'];//, 'user'];
    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all the vendor orders for this order.
     */
    public function vendorOrders()
    {
        return $this->hasMany(VendorOrder::class);
    }

    public function taxes()
    {
        return $this->hasMany(related: OrderTax::class);
    }

    /**
     * Get all the order items for this order.
     */
    public function orderItems()
    {
        return $this->hasManyThrough(OrderItem::class, VendorOrder::class);
    }
    


    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }

    public function shippingAddress()
    {
        return $this->hasOneThrough(
            ShippingAddress::class,
            Shipment::class,
            'order_id', // Foreign key on shipments table
            'id', // Foreign key on shipping_addresses table
            'id', // Local key on orders table
            'shipping_address_id' // Local key on shipments table
        );
    }


    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', OrderStatus::PENDING);
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', OrderStatus::PROCESSING);
    }

    // Status transitions
    public function markAsProcessing()
    {
        $this->update(['status' => OrderStatus::PROCESSING]);
    }

    public function markAsShipped()
    {
        $this->update(['status' => OrderStatus::SHIPPED]);
    }

    public function cancel()
    {
        if (!in_array($this->status, [OrderStatus::SHIPPED, OrderStatus::DELIVERED])) {
            $this->update(['status' => OrderStatus::CANCELLED]);
            return true;
        }
        return false;
    }
}