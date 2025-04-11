<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = ['vendor_id', 'name', 'email', 'phone', 'city_id', 'address'];

    protected $hidden = [
        // 'created_at',
        // 'updated_at',
        // 'deleted_at'
    ];

    protected $attributes = [
    ];

    // protected $with = ['user', 'city'];
    protected function casts(): array
    {
        return [
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}