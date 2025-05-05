<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'rate',
        'is_inclusive',
        'tax_type',
        'tax_id',
        'active',
    ];

    /**
     * Get the taxes for the tax rule.
     */
    public function orderTaxes()
    {
        return $this->hasMany(OrderTax::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}