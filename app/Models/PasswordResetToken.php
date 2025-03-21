<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PasswordResetToken extends Model
{
    use HasFactory;
    protected $table = 'password_reset_tokens';
    protected $fillable = [
        'user_id',
        'email',
        'token',
        'status',
        'reset_attempts',
        'last_attempt_at',
        'expires_at',
    ];
    protected $casts = [
        'last_attempt_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
    
    /**
     * Scope: Retrieve only active tokens.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('expires_at', '>', Carbon::now());
    }
    /**
     * Check if the token is expired.
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
    /**
     * Mark token as used.
     */
    public function markAsUsed()
    {
        $this->update(['status' => 'used']);
    }
    /**
     * Increment reset attempt count.
     */
    public function incrementAttempt()
    {
        $this->increment('reset_attempts');
        $this->update(['last_attempt_at' => Carbon::now()]);
    }
    /**
     * Relationship: The user requesting the reset.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}