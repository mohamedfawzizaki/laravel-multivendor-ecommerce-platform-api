<?php

namespace App\Models\ProductsWarehousesManagement;

use App\Models\Products\Product;
use Illuminate\Database\Eloquent\Model;
use App\Models\Products\ProductVariation;
use App\Models\ProductsWarehousesManagement\WarehouseTransfer;

class WarehouseTransferItem extends Model
{
    protected $fillable = [
        'transfer_id',
        'product_id',
        'variation_id',
        'quantity_requested',
        'quantity_sent',
        'quantity_received',
        'batch_number',
        'expiry_date',
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    public function transfer()
    {
        return $this->belongsTo(WarehouseTransfer::class, 'transfer_id');
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