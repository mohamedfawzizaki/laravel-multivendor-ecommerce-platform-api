<?php

namespace App\Repositories\Repos\Warehouses;

use App\Models\Warehouses\WarehouseRack;
use App\Repositories\RepositoryPropertiesInterface;
use App\Repositories\EloquentBased\MainBaseRepository;

class WarehouseRackRepository extends MainBaseRepository implements RepositoryPropertiesInterface
{
    public array $relationships = [];
    public array $relationshipMap = [];
    public bool $hasPivot = false;
    public array $pivotWith = [];
    public array $defaultIDsForPivot = [];
    public array $fillable = [
        'zone_id',
        'code',
        'name',
        'status'
    ];
    public array $availableColumns = [
        'id',
        'zone_id',
        'code',
        'name',
        'status'
    ];
    public array $availableConditionColumns = [
        'id',
        'zone_id',
        'code',
        'name',
        'status'
    ];
    public array $availableColumnsForMassUpdate = [];

    /**
     * WarehouseRackRepository constructor.
     *
     * @param WarehouseRack $warehouseRack The WarehouseRack model instance.
     */
    public function __construct(WarehouseRack $warehouseRack)
    {
        parent::__construct($warehouseRack);
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