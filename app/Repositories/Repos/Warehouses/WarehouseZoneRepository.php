<?php

namespace App\Repositories\Repos\Warehouses;

use App\Models\Warehouses\WarehouseZone;
use App\Repositories\RepositoryPropertiesInterface;
use App\Repositories\EloquentBased\MainBaseRepository;

class WarehouseZoneRepository extends MainBaseRepository implements RepositoryPropertiesInterface
{
    public array $relationships = [];
    public array $relationshipMap = [];
    public bool $hasPivot = false;
    public array $pivotWith = [];
    public array $defaultIDsForPivot = [];
    public array $fillable = [
        'warehouse_id',
        'code',
        'name',
        'status',
    ];
    public array $availableColumns = [
        'id',
        'warehouse_id',
        'code',
        'name',
        'status',
    ];
    public array $availableConditionColumns = [
        'id', 
        'warehouse_id',
        'code',
        'name',
        'status',
    ];
    public array $availableColumnsForMassUpdate = [];

    /**
     * WarehouseZoneRepository constructor.
     *
     * @param WarehouseZone $warehouseZone The Warehouse model instance.
     */
    public function __construct(WarehouseZone $warehouseZone)
    {
        parent::__construct($warehouseZone);
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