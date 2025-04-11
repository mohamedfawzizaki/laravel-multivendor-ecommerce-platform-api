<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Wishlist extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'session_id',
        'wishlist_name',
        'product_id',
        'variant_id',
        'notes',
        'notify_preferences',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    // Relationship with Product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Relationship with User (for authenticated users)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // For anonymous users (guests), using session ID
    public function session()
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    // Scope for checking expiration
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }
}