<?php

namespace App\Models\Invoices;

use App\Models\User;
use App\Models\Invoices\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceAttachment extends Model
{
    use HasFactory;

    // Attachment types
    public const TYPE_INVOICE_PDF = 'invoice_pdf';
    public const TYPE_RECEIPT = 'receipt';
    public const TYPE_CONTRACT = 'contract';
    public const TYPE_CUSTOM = 'custom';

    protected $fillable = [
        'invoice_id',
        'type',
        'file_path',
        'file_hash',
        'uploaded_by'
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileUrlAttribute(): ?string
    {
        return Storage::exists($this->file_path) 
            ? Storage::url($this->file_path)
            : null;
    }

    public function verifyIntegrity(): bool
    {
        if (!Storage::exists($this->file_path)) {
            return false;
        }

        $currentHash = hash_file('sha256', Storage::path($this->file_path));
        return $currentHash === $this->file_hash;
    }
}