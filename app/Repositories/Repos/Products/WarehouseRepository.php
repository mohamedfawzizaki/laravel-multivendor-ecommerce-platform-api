<?php

namespace App\Repositories\Repos\Products;

use App\Models\Warehouse;
use App\Repositories\RepositoryPropertiesInterface;
use App\Repositories\EloquentBased\MainBaseRepository;

class WarehouseRepository extends MainBaseRepository implements RepositoryPropertiesInterface
{
    // public array $relationships = ['user', 'city'];
    public array $relationships = [];

    public array $relationshipMap = [];
    public bool $hasPivot = false;
    public array $pivotWith = [];
    public array $defaultIDsForPivot = [];
    public array $fillable = ['id', 'vendor_id', 'name', 'email', 'phone', 'city_id', 'address'];
    public array $availableColumns = ['id', 'vendor_id', 'name', 'email', 'phone', 'city_id', 'address'];
    public array $availableConditionColumns = ['id', 'vendor_id', 'name', 'email', 'phone', 'city_id', 'address'];
    public array $availableColumnsForMassUpdate = ['vendor_id', 'city_id', 'phone', 'address'];

    /**
     * WarehouseRepository constructor.
     *
     * @param Warehouse $warehouse The Warehouse model instance.
     */
    public function __construct(Warehouse $warehouse)
    {
        parent::__construct($warehouse);
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