<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Session extends Model
{
    use HasFactory;
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id',
        'user_id',
        'device_id',
        'payload',
        'last_activity',
        'status',
    ];
    protected $casts = [
        'last_activity' => 'datetime',
    ];
    /**
     * Scope: Find active sessions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    /**
     * Mark session as expired.
     */
    public function expire()
    {
        $this->update(['status' => 'expired']);
    }
    /**
     * Mark session as revoked.
     */
    public function revoke()
    {
        $this->update(['status' => 'revoked']);
    }
    /**
     * Relationship: The user who owns this session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}