<?php

namespace App\Models\Warehouses;

use Illuminate\Database\Eloquent\Model;

class WarehouseShelf extends Model
{
    protected $fillable = [
        'rack_id',
        'code',
        'name',
        'status'
    ];

    public function rack()
    {
        return $this->belongsTo(WarehouseRack::class);
    }

    public function bins()
    {
        return $this->hasMany(WarehouseBin::class, 'shelf_id');
    }
}