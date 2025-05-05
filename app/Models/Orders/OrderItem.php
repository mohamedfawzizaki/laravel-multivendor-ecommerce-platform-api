<?php

namespace App\Models\Orders;

use App\Models\Products\Product;
use App\Models\Orders\VendorOrder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Products\ProductVariation;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItem extends Model
{
    use HasFactory;

    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'vendor_order_id',
        'product_id',
        'variation_id',
        'quantity',
        'price',
        'subtotal',
        'is_digital',
        'download_url',
        'download_expiry',
        'is_returnable',
        'return_by_date',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * The vendor order this item belongs to.
     */
    public function vendorOrder()
    {
        return $this->belongsTo(VendorOrder::class);
    }

    /**
     * The product this item refers to.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * The product variation (optional) for this item.
     */
    public function variation()
    {
        return $this->belongsTo(ProductVariation::class);
    }

    public function getSubtotalAttribute($value)
    {
        return $value ?? $this->price * $this->quantity;
    }
}