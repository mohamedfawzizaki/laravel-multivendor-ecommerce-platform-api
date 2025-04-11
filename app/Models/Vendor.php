<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['user_id', 'business_description', 'business_name', 'documentation_url', 'logo_url', 'status', 'approved_at'];

    protected $hidden = [
        // 'created_at',
        // 'updated_at',
        // 'deleted_at'
    ];

    protected $attributes = [
    ];

    protected $with = ['user'];
    protected function casts(): array
    {
        return [];
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}