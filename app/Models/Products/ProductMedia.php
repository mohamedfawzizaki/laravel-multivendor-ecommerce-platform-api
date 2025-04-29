<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'variation_id',
        'type',
        'path',
        'mime_type',
        'file_size',
        'sort_order',
        'metadata',
        'is_default'
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_default' => 'boolean',
        'file_size' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class);
    }

    protected static function booted()
    {
        static::saving(function ($media) {
            if ($media->is_default) {
                self::where('product_id', $media->product_id)
                    ->where('id', '!=', $media->id)
                    ->update(['is_default' => false]);
            }
        });
    }
}