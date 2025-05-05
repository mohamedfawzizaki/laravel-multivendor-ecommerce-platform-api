<?php

namespace App\Models\Invoices;

use App\Models\Invoices\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceTax extends Model
{
    use HasFactory;

    // Tax types
    public const TYPE_VAT = 'vat';
    public const TYPE_GST = 'gst';
    public const TYPE_SALES_TAX = 'sales_tax';
    public const TYPE_SERVICE_TAX = 'service_tax';

    protected $fillable = [
        'invoice_id',
        'tax_name',
        'tax_rate',
        'tax_amount',
        'tax_type'
    ];

    protected $casts = [
        'tax_rate' => 'decimal:5',
        'tax_amount' => 'decimal:12'
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function getFormattedRateAttribute(): string
    {
        return number_format($this->tax_rate, 2) . '%';
    }

    // public function getFormattedAmountAttribute(): string
    // {
    //     return currency_format($this->tax_amount, $this->invoice->currency_code);
    // }
}