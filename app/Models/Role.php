<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', // The role name (e.g., 'admin', 'customer').
        'description', // Additional information about the role.
    ];

    /**
     * The attributes that should be hidden when the model is serialized.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'created_at', // Hide timestamps when returning data.
        'updated_at',
    ];

    /**
     * Relationships to be touched when this model is updated.
     *
     * @var array<int, string>
     */
    protected $touches = ['permissions'];

    /**
     * Eager-load relationships by default.
     *
     * @var array<int, string>
     */
    protected $with = ['permissions'];

    /**
     * Define the relationship: A Role has many Users.
     *
     * @return HasMany
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }

    /**
     * Define the many-to-many relationship between roles and permissions.
     *
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

}