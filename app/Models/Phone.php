<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Phone extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'phone',       
        'is_primary', 
        'user_id',         
    ];

    protected $hidden = [
        'phone_verified_at',
        'phone_verification_code',
        'phone_verification_expires_at',
        // 'created_at',
        // 'updated_at',
        // 'deleted_at'
    ];

    protected $attributes = [
        'phone_verified_at' => null, // Not verified by default
        'phone_verification_code' => null,
        'phone_verification_expires_at' => null,
    ];

    // protected $with = ['user'];
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verification_expires_at' => 'datetime',
        ];
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}