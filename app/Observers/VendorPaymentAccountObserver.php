<?php

namespace App\Observers;


use Illuminate\Support\Facades\Auth;
use App\Events\PaymentAccountVerified;
use App\Events\PaymentAccountSuspended;
use App\Models\Vendors\PaymentAccountAudit;
use App\Models\Vendors\VendorPaymentAccount;

class VendorPaymentAccountObserver
{
    public function updated(VendorPaymentAccount $account)
    {
        if ($account->isDirty('verification_status')) {
            match ($account->verification_status) {
                // VendorPaymentAccount::VERIFICATION_VERIFIED => 
                //     event(new PaymentAccountVerified($account)),
                // VendorPaymentAccount::VERIFICATION_SUSPENDED => 
                //     event(new PaymentAccountSuspended($account)),
                // default => null
            };
        }
    }

    public function created(VendorPaymentAccount $account)
    {
        $account->audits()->create([
            'event_type' => PaymentAccountAudit::EVENT_CREATED,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'performed_by' => Auth::id()
        ]);
    }
}