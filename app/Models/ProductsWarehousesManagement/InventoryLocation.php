<?php

namespace App\Models\ProductsWarehousesManagement;

use App\Models\Products\Product;
use App\Models\Warehouses\Warehouse;
use App\Models\Warehouses\WarehouseBin;
use Illuminate\Database\Eloquent\Model;
use App\Models\Products\ProductVariation;

class InventoryLocation extends Model
{
    protected $fillable = [
        'warehouse_id',
        'bin_id',
        'product_id',
        'variation_id',
        'quantity',
        'batch_number',
        'expiry_date',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function bin()
    {
        return $this->belongsTo(WarehouseBin::class, 'bin_id');
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