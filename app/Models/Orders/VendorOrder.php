<?php

namespace App\Models\Orders;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VendorOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vendor_orders';

    protected $fillable = [
        'order_id',
        'vendor_id',
        'vendor_order_number',
        'subtotal',
        'tax',
        'commission_amount',
        'total_price',
        'status',
        'fulfillment_type',
        'vendor_notes'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public static function generateOrderNumber()
    {
        return 'VORD-' . date('Ymd') . '-' . strtoupper(Str::random(6));
    }
    
    /**
     * The order this vendor order belongs to.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * The vendor (seller) this vendor order belongs to.
     */
    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function orderItems()
    {
        return $this->hasMany(related: OrderItem::class);
    }

}