<?php

namespace App\Models\Shipping;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Models\Shipping\ShippingCarrier;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingMethod extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'carrier_id',
        'name',
        'code',
        'external_id',
        'calculation_type',
        'base_price',
        'min_order_amount',
        'max_order_weight',
        'rate_table',
        'min_delivery_days',
        'max_delivery_days',
        'weekend_delivery',
        'cash_on_delivery',
        'supported_zones',
        'excluded_products',
        'carrier_config',
        'is_active',
        'is_default',
        'is_integrated',
        'vendor_fee',
        'platform_fee',
        'tax_rate',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'rate_table' => 'array',
        'supported_zones' => 'array',
        'excluded_products' => 'array',
        'carrier_config' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'is_integrated' => 'boolean',
        'weekend_delivery' => 'boolean',
        'cash_on_delivery' => 'boolean',
        'base_price' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_order_weight' => 'decimal:3',
        'vendor_fee' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'tax_rate' => 'decimal:2',
    ];

    // Relationships

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(ShippingCarrier::class, 'carrier_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}