<?php

namespace App\Models\Warehouses;

use Illuminate\Database\Eloquent\Model;

class WarehouseBin extends Model
{
    protected $fillable = [
        'shelf_id',
        'code',
        'name',
        'bin_type',
        'width',
        'height',
        'depth',
        'max_weight',
        'status'
    ];

    protected $casts = [
        'width' => 'float',
        'height' => 'float',
        'depth' => 'float',
        'max_weight' => 'float',
    ];

    protected $appends = ['volume_cm3'];

    public function shelf()
    {
        return $this->belongsTo(WarehouseShelf::class);
    }

    public function getVolumeCm3Attribute()
    {
        return ($this->width ?? 0) * ($this->height ?? 0) * ($this->depth ?? 0);
    }
}