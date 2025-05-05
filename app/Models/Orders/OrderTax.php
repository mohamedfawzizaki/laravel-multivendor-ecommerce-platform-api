<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderTax extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'vendor_order_id',
        'tax_rule_id',
        'tax_name',
        'tax_rate',
        'tax_amount',
        'is_inclusive',
        'tax_id',
        'tax_type',
    ];

    protected $casts = [
        'tax_rate' => 'decimal:5',
        'tax_amount' => 'decimal:10',
        'is_inclusive' => 'boolean',
    ];

    /**
     * Get the order this tax belongs to
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the vendor order this tax belongs to (if applicable)
     */
    public function vendorOrder(): BelongsTo
    {
        return $this->belongsTo(VendorOrder::class);
    }

    /**
     * Get the tax rule that owns the order tax.
     */
    public function taxRule()
    {
        return $this->belongsTo(TaxRule::class);
    }

    /**
     * Scope for inclusive taxes
     */
    public function scopeInclusive($query)
    {
        return $query->where('is_inclusive', true);
    }

    /**
     * Scope for exclusive taxes
     */
    public function scopeExclusive($query)
    {
        return $query->where('is_inclusive', false);
    }

    /**
     * Get formatted tax rate (e.g., 15.00%)
     */
    public function getFormattedRateAttribute(): string
    {
        return number_format($this->tax_rate, 2) . '%';
    }
}