<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Status extends Model
{
    use HasFactory;
    /**
     * The table does not have a `created_at` column, so we disable timestamps.
     *
     * @var bool
     */
    public $timestamps = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',         // Status name (e.g., 'active', 'inactive')
        'description',  // Optional description
    ];

    /**
     * The attributes that should be hidden when the model is serialized.
     * This is useful for preventing internal timestamps from being exposed in JSON responses.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'updated_at', // Hide the update timestamp.
    ];
    /**
     * Define the relationship: A Status has many Users.
     *
     * @return HasMany
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'status_id');
    }
}