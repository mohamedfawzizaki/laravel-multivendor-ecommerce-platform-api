<?php

namespace App\Models\Warehouses;

use App\Models\City;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'code',
        'name',
        'contact_name',
        'contact_email',
        'contact_phone',
        'total_capacity',
        'city_id',
        'status',
        'latitude',
        'longitude',
        'priority',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'total_capacity' => 'integer',
        'priority' => 'integer',
        'status' => 'string',
    ];

    // Relationships

    /**
     * Vendor who owns the warehouse.
     */
    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * City in which the warehouse is located.
     */
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Zones within the warehouse.
     */
    public function zones()
    {
        return $this->hasMany(WarehouseZone::class);
    }

    // Scopes

    /**
     * Scope for active warehouses.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to sort by priority (ascending = higher priority).
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }

    // Accessors / Mutators (Optional)

    // public function getFullLocationAttribute(): string
    // {
    //     return "{$this->name}, {$this->city->name}, {$this->city->country()->name}";
    // }

    // Additional Logic

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}