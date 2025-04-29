<?php

namespace App\Models\ProductsWarehousesManagement;

use App\Models\User;
use App\Models\Warehouses\Warehouse;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProductsWarehousesManagement\WarehouseTransferItem;

class WarehouseTransfer extends Model
{
    protected $fillable = [
        'transfer_number',
        'status',
        'from_warehouse_id',
        'to_warehouse_id',
        'notes',
        'created_by',
        'approved_by',
        'expected_transfer_date',
        'completed_at',
    ];

    protected $casts = [
        'expected_transfer_date' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(WarehouseTransferItem::class, 'transfer_id');
    }
}