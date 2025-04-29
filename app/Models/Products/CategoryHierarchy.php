<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CategoryHierarchy extends Model
{
    use HasFactory;

    public $timestamps = false; // 👈 this disables created_at and updated_at
    protected $table = 'category_hierarchy';
    protected $fillable = [
        'parent_id',
        'child_id'
    ];

    protected $hidden = [
    ];

    protected $attributes = [
    ];

    protected function casts(): array
    {
        return [
        ];
    }
    
    // Define relationships
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function child()
    {
        return $this->belongsTo(Category::class, 'child_id');
    }
}