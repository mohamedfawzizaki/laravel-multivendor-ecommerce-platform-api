<?php

namespace App\Models\Shipping;

use App\Models\City;
use App\Models\User;
use App\Models\Shipping\Shipment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShippingAddress extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'city_id',
        'address_line1',
        'address_line2',
        'postal_code',
        'recipient_name',
        'recipient_phone',
        'company_name',
        'is_default'
    ];

    protected $casts = [
        'is_default' => 'boolean'
    ];

    // Relationships
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // Helpers
    public function getFullAddressAttribute()
    {
        $address = $this->address_line1;

        if ($this->address_line2) {
            $address .= ', ' . $this->address_line2;
        }

        $address .= ', ' . $this->city->name;
        $address .= ', ' . $this->postal_code;

        if ($this->city->state) {
            $address .= ', ' . $this->city->state->name;
        }

        $address .= ', ' . $this->city->country->name;

        return $address;
    }

    public function makeDefault()
    {
        // Remove default status from other addresses
        $this->user->shippingAddresses()->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }
}