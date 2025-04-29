<?php

namespace App\Repositories\Repos\Warehouses;

use App\Models\Warehouses\WarehouseShelf;
use App\Repositories\RepositoryPropertiesInterface;
use App\Repositories\EloquentBased\MainBaseRepository;

class WarehouseShelfRepository extends MainBaseRepository implements RepositoryPropertiesInterface
{
    public array $relationships = [];
    public array $relationshipMap = [];
    public bool $hasPivot = false;
    public array $pivotWith = [];
    public array $defaultIDsForPivot = [];
    public array $fillable = [
        'rack_id',
        'code',
        'name',
        'status'
    ];
    public array $availableColumns = [
        'id',
        'rack_id',
        'code',
        'name',
        'status'
    ];
    public array $availableConditionColumns = [
        'id', 
        'rack_id',
        'code',
        'name',
        'status'
    ];
    public array $availableColumnsForMassUpdate = [];

    /**
     * WarehouseShelfRepository constructor.
     *
     * @param WarehouseShelf $warehouseShelf The Warehouse model instance.
     */
    public function __construct(WarehouseShelf $warehouseShelf)
    {
        parent::__construct($warehouseShelf);
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