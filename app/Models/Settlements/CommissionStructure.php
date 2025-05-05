<?php

namespace App\Models\Settlements;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CommissionStructure extends Model
{
    protected $fillable = [
        'vendor_id',
        'name',
        'type',
        'value',
        'configuration',
        'valid_from',
        'valid_to',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'configuration' => 'array',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    // Scope: active only
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('valid_to')->orWhere('valid_to', '>=', now());
            });
    }
}