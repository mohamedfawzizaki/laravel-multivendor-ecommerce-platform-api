<?php

namespace App\Models\Invoices;

use App\Models\Invoices\Invoice;
use App\Models\Payments\OrderPayment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoicePayment extends Model
{
    use HasFactory;

    public const TYPE_FULL = 'full';
    public const TYPE_PARTIAL = 'partial';
    public const TYPE_ADVANCE = 'advance';
    public const TYPE_INSTALLMENT = 'installment';

    protected $fillable = [
        'invoice_id',
        'payment_id',
        'amount',
        'payment_date',
        'payment_type',
        'transaction_id',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(OrderPayment::class);
    }

    public function isAdvance(): bool
    {
        return $this->payment_type === self::TYPE_ADVANCE;
    }

    public function isInstallment(): bool
    {
        return $this->payment_type === self::TYPE_INSTALLMENT;
    }

    public function isPartial(): bool
    {
        return $this->payment_type === self::TYPE_PARTIAL;
    }
}