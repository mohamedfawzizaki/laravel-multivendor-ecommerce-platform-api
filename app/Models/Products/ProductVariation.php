<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'variant_name',
        'sku',
        'price',
        'compare_price',
        'attributes'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'attributes' => 'array',
    ];

    // Relationships
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereHas('product', function($q) {
            $q->where('status', 'active');
        });
    }

    // Validation rules
    public static function rules()
    {
        return [
            'variant_name' => 'required|string|max:255',
            'sku' => 'required|unique:product_variations,sku',
            'price' => 'required|numeric|min:0',
            'attributes' => 'nullable|array',
        ];
    }
}