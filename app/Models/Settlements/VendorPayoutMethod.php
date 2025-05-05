<?php

namespace App\Models\Settlements;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Encryption\DecryptException;

class VendorPayoutMethod extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'type',
        'details',
        'is_primary',
        'is_verified',
        'verified_at',
        'verified_by',
    ];

    protected $casts = [
        'details' => 'array', // still encrypted manually
        'is_primary' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    // Relationships
    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Encrypted accessors/mutators for details field
    public function setDetailsAttribute($value)
    {
        $this->attributes['details'] = Crypt::encryptString(json_encode($value));
    }

    public function getDetailsAttribute($value)
    {
        try {
            return json_decode(Crypt::decryptString($value), true);
        } catch (DecryptException $e) {
            return null;
        }
    }
}