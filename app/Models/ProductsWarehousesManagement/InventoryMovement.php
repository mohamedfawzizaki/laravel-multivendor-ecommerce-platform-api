<?php

namespace App\Models\ProductsWarehousesManagement;

use App\Models\User;
use App\Models\Products\Product;
use App\Models\Warehouses\Warehouse;
use Illuminate\Database\Eloquent\Model;
use App\Models\Products\ProductVariation;

class InventoryMovement extends Model
{
    protected $fillable = [
        'mover_id',
        'warehouse_id',
        'product_id',
        'variation_id',
        'movement_type',
        'quantity_change',
        'quantity_before',
        'quantity_after',
        'notes',
    ];

    protected $casts = [
        'movement_type' => 'string',
    ];

    public function mover()
    {
        return $this->belongsTo(User::class, 'mover_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variation()
    {
        return $this->belongsTo(ProductVariation::class, 'variation_id');
    }
}