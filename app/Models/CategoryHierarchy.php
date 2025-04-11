<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CategoryHierarchy extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'category_hierarchy';
    protected $fillable = [
        'parent_id',
        'child_id'
    ];

    protected $hidden = [
        // 'created_at',
        // 'updated_at',
        // 'deleted_at'
    ];

    protected $attributes = [
    ];

    protected $with = ['category'];
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