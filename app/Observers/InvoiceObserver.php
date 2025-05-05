<?php

namespace App\Observers;


use App\Events\InvoicePaid;
use App\Events\InvoiceIssued;
use App\Models\Invoices\Invoice;

class InvoiceObserver
{
    public function created(Invoice $invoice)
    {
        if ($invoice->status === Invoice::STATUS_ISSUED) {
            // event(new InvoiceIssued($invoice));
        }
    }

    public function updated(Invoice $invoice)
    {
        if ($invoice->isDirty('status') && $invoice->status === Invoice::STATUS_PAID) {
            // event(new InvoicePaid($invoice));
        }
    }
}