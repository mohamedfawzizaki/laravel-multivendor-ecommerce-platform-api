<?php

namespace App\Models\Invoices;

use App\Models\User;
use App\Models\Invoices\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceLedger extends Model
{
    use HasFactory;

    public const TYPE_DEBIT = 'debit';
    public const TYPE_CREDIT = 'credit';
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'invoice_id',
        'entry_type',
        'amount',
        'description',
        'recorded_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function isCredit(): bool
    {
        return $this->entry_type === self::TYPE_CREDIT;
    }

    public function isDebit(): bool
    {
        return $this->entry_type === self::TYPE_DEBIT;
    }
}