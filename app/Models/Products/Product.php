<?php

namespace App\Models\Products;

use App\Models\User;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'brand_id',
        'category_id',
        'name',
        'slug',
        'description',
        'base_price',
        'base_compare_price',
        'status',
        'currency_code'
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'base_compare_price' => 'decimal:2',
        'status' => 'string',
    ];

    // Relationships
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }

    // Helper methods
    public function isVariable(): bool
    {
        return $this->base_price === null && $this->variations()->exists();
    }

    public function getPriceAttribute()
    {
        return $this->isVariable()
            ? $this->variations->min('price')
            : $this->base_price;
    }

    public function media()
    {
        return $this->hasMany(ProductMedia::class);
    }

    public function discounts()
    {
        return $this->hasMany(ProductDiscount::class);
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }
}