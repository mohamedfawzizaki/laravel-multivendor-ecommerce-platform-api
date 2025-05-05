<?php

namespace App\Notifications;

use App\Models\Payments\PaymentRefund;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class RefundProcessedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public PaymentRefund $refund)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Refund processed for order #{$this->refund->order->order_number}")
            ->line("A refund of {$this->refund->formatted_amount} has been processed")
            ->line("Reason: {$this->refund->refund_reason}")
            ->action('View Order', route('orders.show', $this->refund->order))
            ->line('Thank you for your patience!');
    }

    public function toArray($notifiable): array
    {
        return [
            'refund_id' => $this->refund->id,
            'order_id' => $this->refund->order_id,
            'amount' => $this->refund->amount,
            'message' => 'Refund processed for your order',
            'url' => route('orders.show', $this->refund->order)
        ];
    }
}