<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;


class ProductDiscount extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'product_id',
        'discount_price',
        'discount_percentage',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date'=> 'datetime',  
        'end_date'=> 'datetime',  
    ];

    // protected $with = ['product'];
    /**
     * Relationship: Belongs to a product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}