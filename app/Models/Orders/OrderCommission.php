<?php

namespace App\Models\Orders;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_order_id',
        'vendor_id',
        'amount',
        'rate',
        'commission_type',
        'is_paid',
        'paid_date'
    ];

    protected $casts = [
        'amount' => 'decimal:10',
        'rate' => 'decimal:5',
        'is_paid' => 'boolean',
        'paid_date' => 'date'
    ];

    /**
     * Commission types
     */
    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED = 'fixed';

    /**
     * Get the vendor order this commission belongs to
     */
    public function vendorOrder(): BelongsTo
    {
        return $this->belongsTo(VendorOrder::class);
    }

    /**
     * Get the vendor this commission belongs to
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * Scope for paid commissions
     */
    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    /**
     * Scope for unpaid commissions
     */
    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
    }

    /**
     * Mark commission as paid
     */
    public function markAsPaid(): bool
    {
        return $this->update([
            'is_paid' => true,
            'paid_date' => now()
        ]);
    }

    /**
     * Get formatted amount with currency
     */
    public function getFormattedAmountAttribute()//: string
    {
        // return currency_format($this->amount, $this->vendorOrder->order->currency_code);
    }
}