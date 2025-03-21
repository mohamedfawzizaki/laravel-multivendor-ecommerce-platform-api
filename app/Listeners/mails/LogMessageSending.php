<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogMessageSending
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(MessageSending $event): void
    {
        //
        Log::info('Mail is being sent to: ' . json_encode($event->message->getTo()));
    }
}