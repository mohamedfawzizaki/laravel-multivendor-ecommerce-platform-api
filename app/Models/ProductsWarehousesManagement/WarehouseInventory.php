<?php

namespace App\Models\ProductsWarehousesManagement;

use App\Models\Products\Product;
use App\Models\Warehouses\Warehouse;
use Illuminate\Database\Eloquent\Model;
use App\Models\Products\ProductVariation;

class WarehouseInventory extends Model
{
    protected $table = 'warehouse_inventory';
    protected $fillable = [
        'warehouse_id',
        'product_id',
        'variation_id',
        'quantity_on_hand',
        'quantity_allocated',
        'quantity_on_hold',
        'low_stock_threshold',
        'reorder_quantity',
        'location_code',
    ];

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