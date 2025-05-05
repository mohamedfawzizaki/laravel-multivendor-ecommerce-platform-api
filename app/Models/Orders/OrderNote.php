<?php

namespace App\Models\Orders;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderNote extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'order_id',
        'user_id',
        'note',
        'type',
        'notify_customer',
        'is_pinned'
    ];

    protected $casts = [
        'notify_customer' => 'boolean',
        'is_pinned' => 'boolean'
    ];

    /**
     * Note types
     */
    public const TYPE_CUSTOMER = 'customer';
    public const TYPE_INTERNAL = 'internal';
    public const TYPE_SYSTEM = 'system';

    /**
     * Get the order this note belongs to
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user who created this note
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope for customer visible notes
     */
    public function scopeCustomerVisible($query)
    {
        return $query->where('type', self::TYPE_CUSTOMER)
                    ->orWhere('notify_customer', true);
    }

    /**
     * Scope for pinned notes
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Check if note is system generated
     */
    public function isSystemGenerated(): bool
    {
        return $this->type === self::TYPE_SYSTEM;
    }

    /**
     * Notify customer about this note
     */
    public function notifyCustomer()
    {
        // if ($this->notify_customer && !$this->isSystemGenerated()) {
        //     $this->order->user->notify(new OrderNoteNotification($this));
        // }
    }
}