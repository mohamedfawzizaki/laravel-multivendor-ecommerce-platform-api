<?php

namespace App\Models\Shipping;

use App\Models\User;
use App\Models\Shipping\Shipment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShippingCarrier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'code',
        'name',
        'tracking_url_format',
        'customer_service_phone',
        'customer_service_email',
        'website_url',
        'is_active',
        'service_levels'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'service_levels' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    // Relationships
    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helpers
    public function getTrackingUrl($trackingNumber)
    {
        return $this->tracking_url_format
            ? str_replace('{{tracking_number}}', $trackingNumber, $this->tracking_url_format)
            : null;
    }

    public function getServiceLevelOptions()
    {
        return $this->service_levels ?? [];
    }
}