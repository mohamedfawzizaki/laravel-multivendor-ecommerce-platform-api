<?php

namespace App\Models;

use App\Models\Cart;
use App\Models\City;
use App\Models\Orders\Order;
use App\Models\Role;
use App\Models\Phone;
use App\Models\Status;
use App\Models\Shipping\Shipment;
use Laravel\Sanctum\HasApiTokens;
use Database\Factories\UserFactory;
use App\Models\Shipping\ShippingAddress;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasUuids, SoftDeletes, HasApiTokens;

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',      // Unique name
        'email',         // Unique email address
        'password',      // Hashed password
        'role_id',
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'role_id',
        'status_id',
        'password',
        'remember_token',
        'email_verified_at',
        'email_verification_code',
        'email_verification_token',
        'email_verification_expires_at',
        'last_login_at',
        // 'created_at',
        // 'updated_at',
        // 'deleted_at'
    ];
    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'role_id' => 10, // Default role (e.g., User role)
        'status_id' => 1, // Default status (e.g., Active)
        'email_verified_at' => null, // Not verified by default
        'email_verification_code' => null,
        'email_verification_expires_at' => null,
        'last_login_at' => null,
    ];
    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['role.permissions', 'status', 'phone', 'addresses'];
    /**
     * All of the relationships to be touched.
     *
     * @var array
     */
    protected $touches = ['role', 'status', 'phone', 'addresses'];
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verification_expires_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class)->withDefault(function (Role $role) {
            $role->id = Role::where('name', 'customer')->value('id');
        });
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function phone(): HasMany
    {
        return $this->hasMany(Phone::class);
    }

    public function addresses(): BelongsToMany
    {
        return $this->belongsToMany(City::class, 'user_address');
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function shippingAddresses()
    {
        return $this->hasMany(ShippingAddress::class);
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}