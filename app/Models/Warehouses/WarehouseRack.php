<?php

namespace App\Models\Warehouses;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseRack extends Model
{
    protected $fillable = [
        'zone_id',
        'code',
        'name',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(WarehouseZone::class);
    }

    public function shelves(): HasMany
    {
        return $this->hasMany(WarehouseShelf::class, 'rack_id');
    }
}