<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'variant_name',
        'price',
        'stock',
        'sku',
        'attributes',
    ];

    protected $casts = [
        'attributes' => 'array', // Laravel handles JSON column as array
        'price' => 'decimal:2',
        'stock' => 'integer',
    ];

    // protected $with = ['product'];

    /**
     * Relationship: Belongs to a product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope: Filter by attribute key and value.
    */
    public function scopeWhereAttribute($query, $key, $value)
    {
        return $query->whereJsonContains("attributes->{$key}", $value);
    }
}