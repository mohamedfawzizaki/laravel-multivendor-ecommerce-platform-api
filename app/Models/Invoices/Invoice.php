<?php

namespace App\Models\Invoices;

use App\Models\User;
use App\Models\Orders\Order;
use App\Models\Orders\VendorOrder;
use App\Models\Invoices\InvoiceTax;
use App\Models\Invoices\InvoiceItem;
use Illuminate\Database\Eloquent\Model;
use App\Models\Invoices\InvoiceAttachment;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    // Invoice types
    public const TYPE_PLATFORM = 'platform';
    public const TYPE_VENDOR = 'vendor';
    public const TYPE_SUBSCRIPTION = 'subscription';
    public const TYPE_CREDIT_NOTE = 'credit_note';
    public const TYPE_PROFORMA = 'proforma';
    public const TYPE_RECURRING = 'recurring';

    // Payment terms
    public const TERM_NET_7 = 'net_7';
    public const TERM_NET_15 = 'net_15';
    public const TERM_NET_30 = 'net_30';
    public const TERM_DUE_ON_RECEIPT = 'due_on_receipt';
    public const TERM_CUSTOM = 'custom';

    // Statuses
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_PAID = 'paid';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_VOID = 'void';
    public const STATUS_DISPUTED = 'disputed';

    protected $fillable = [
        'vendor_id',
        'order_id',
        'vendor_order_id',
        'invoice_number',
        'type',
        'billing_address',
        'shipping_address',
        'tax_id',
        'vat_number',
        'subtotal',
        'tax_amount',
        'shipping_cost',
        'discount_amount',
        'total_amount',
        'tax_breakdown',
        'discount_details',
        'payment_terms',
        'due_date',
        'late_fee_percentage',
        'status',
        'currency_code',
        'exchange_rate',
        'issued_by',
        'issued_at',
        'paid_at',
        'created_from',
        'digital_signature'
    ];

    protected $casts = [
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'tax_breakdown' => 'array',
        'discount_details' => 'array',
        'subtotal' => 'decimal:12',
        'tax_amount' => 'decimal:12',
        'shipping_cost' => 'decimal:12',
        'discount_amount' => 'decimal:12',
        'total_amount' => 'decimal:12',
        'late_fee_percentage' => 'decimal:5',
        'exchange_rate' => 'decimal:6',
        'due_date' => 'date',
        'issued_at' => 'datetime',
        'paid_at' => 'datetime'
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function vendorOrder(): BelongsTo
    {
        return $this->belongsTo(VendorOrder::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(InvoiceTax::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(InvoiceAttachment::class);
    }

    public function markAsPaid(): bool
    {
        return $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now()
        ]);
    }

    public function isOverdue(): bool
    {
        return $this->due_date && 
               $this->status === self::STATUS_ISSUED && 
               $this->due_date->isPast();
    }

    public function calculateLateFee(): float
    {
        if ($this->isOverdue()) {
            return $this->total_amount * ($this->late_fee_percentage / 100);
        }
        return 0;
    }

    // public function getFormattedTotalAttribute(): string
    // {
    //     return currency_format($this->total_amount, $this->currency_code);
    // }
}