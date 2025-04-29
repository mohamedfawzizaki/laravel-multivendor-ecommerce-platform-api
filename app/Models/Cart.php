<?php

namespace App\Models;

use App\Models\Products\Product;
use App\Models\Products\ProductVariation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cart extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'session_id',
        'product_id',
        'variation_id',

        'quantity',
        'price',
        'currency_code',
        'notes',
        'expires_at',
    ];

    protected $hidden = [
        // 'created_at',
        // 'updated_at',
        // 'deleted_at'
    ];

    protected $attributes = [];

    protected $with = ['product', 'variation'];
    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class);
    }
}