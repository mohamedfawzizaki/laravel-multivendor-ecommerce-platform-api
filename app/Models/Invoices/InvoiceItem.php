<?php

namespace App\Models\Invoices;

use App\Models\Invoices\Invoice;
use App\Models\Orders\OrderItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceItem extends Model
{
    use HasFactory;

    // Discount types
    public const DISCOUNT_PERCENTAGE = 'percentage';
    public const DISCOUNT_FIXED = 'fixed';

    protected $fillable = [
        'invoice_id',
        'order_item_id',
        'description',
        'sku',
        'quantity',
        'unit_price',
        'tax_rate',
        'discount_rate',
        'discount_type'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:12',
        'tax_rate' => 'decimal:5',
        'discount_rate' => 'decimal:5'
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function getDiscountAmountAttribute(): float
    {
        return $this->discount_type === self::DISCOUNT_PERCENTAGE
            ? $this->subtotal * ($this->discount_rate / 100)
            : $this->discount_rate;
    }

    // public function getFormattedUnitPriceAttribute(): string
    // {
    //     return currency_format($this->unit_price, $this->invoice->currency_code);
    // }
}