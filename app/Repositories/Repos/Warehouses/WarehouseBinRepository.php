<?php

namespace App\Repositories\Repos\Warehouses;

use App\Models\Warehouses\WarehouseBin;
use App\Repositories\RepositoryPropertiesInterface;
use App\Repositories\EloquentBased\MainBaseRepository;

class WarehouseBinRepository extends MainBaseRepository implements RepositoryPropertiesInterface
{
    public array $relationships = [];
    public array $relationshipMap = [];
    public bool $hasPivot = false;
    public array $pivotWith = [];
    public array $defaultIDsForPivot = [];
    public array $fillable = [
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
    public array $availableColumns = [
        'id',
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
    public array $availableConditionColumns = [
        'id', 
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
    public array $availableColumnsForMassUpdate = [];

    /**
     * WarehouseRepository constructor.
     *
     * @param WarehouseBin $warehouseBin The Warehouse model instance.
     */
    public function __construct(WarehouseBin $warehouseBin)
    {
        parent::__construct($warehouseBin);
    }

    public function getRelationships(): array
    {
        return $this->relationships;
    }

    public function getRelationshipMap(): array
    {
        return $this->relationshipMap;
    }

    public function getFillable(): array
    {
        return $this->fillable;
    }

    public function getAvailableColumns(): array
    {
        return $this->availableColumns;
    }

    public function getAvailableConditionColumns(): array
    {
        return $this->availableConditionColumns;
    }

    public function getAvailableColumnsForMassUpdate(): array
    {
        return $this->availableColumnsForMassUpdate;
    }

    public function hasPivot(): bool
    {
        return $this->hasPivot;
    }
    public function getPivotWith(): array
    {
        return $this->pivotWith;
    }
    public function getDefualtIDsForPivot(): array
    {
        return $this->defaultIDsForPivot;
    }
}