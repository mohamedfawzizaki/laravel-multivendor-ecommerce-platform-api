<?php

namespace App\Models\Invoices;

use App\Models\User;
use App\Models\Invoices\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceReminder extends Model
{
    use HasFactory;

    public const TYPE_PAYMENT_DUE = 'payment_due';
    public const TYPE_OVERDUE = 'payment_overdue';
    public const TYPE_FINAL_NOTICE = 'final_notice';

    protected $fillable = [
        'invoice_id',
        'reminder_type',
        'sent_at',
        'sent_via',
        'sent_by',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function isEscalated(): bool
    {
        return in_array($this->reminder_type, [self::TYPE_OVERDUE, self::TYPE_FINAL_NOTICE]);
    }
}