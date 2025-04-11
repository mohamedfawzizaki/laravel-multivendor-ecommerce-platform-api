<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Image extends Model
{
    use HasFactory;

    protected $table = 'product_images';
    
    protected $fillable = [
        'product_id',
        'image_url',
        'is_primary',
    ];

    protected $hidden = [
        // 'created_at',
        // 'updated_at',
        // 'deleted_at'
    ];

    protected $attributes = [
    ];

    protected function casts(): array
    {
        return [
        ];
    }
    
    // public function product(): BelongsTo
    // {
    //     return $this->belongsTo(Product::class);
    // }
}