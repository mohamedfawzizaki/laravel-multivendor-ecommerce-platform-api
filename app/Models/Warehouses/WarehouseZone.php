<?php

namespace App\Models\Warehouses;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseZone extends Model
{
    protected $fillable = [
        'warehouse_id',
        'code',
        'name',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function racks(): HasMany
    {
        return $this->hasMany(WarehouseRack::class, 'zone_id');
    }
}