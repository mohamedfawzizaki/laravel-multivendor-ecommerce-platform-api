<?php

namespace App\Repositories\Repos\Products;

use App\Models\ProductInventory;
use App\Repositories\RepositoryPropertiesInterface;
use App\Repositories\EloquentBased\MainBaseRepository;

class ProductInventoryRepository extends MainBaseRepository implements RepositoryPropertiesInterface
{
    // public array $relationships = ['warehouse', 'product'];
    public array $relationships = [];

    public array $relationshipMap = [];
    public bool $hasPivot = false;
    public array $pivotWith = [];
    public array $defaultIDsForPivot = [];
    public array $fillable = ['warehouse_id', 'product_id', 'quantity_in_stock', 'restock_threshold', 'last_restocked_at'];
    public array $availableColumns = ['id', 'warehouse_id', 'product_id', 'quantity_in_stock', 'restock_threshold', 'last_restocked_at'];
    public array $availableConditionColumns = ['id', 'warehouse_id', 'product_id', 'quantity_in_stock', 'restock_threshold', 'last_restocked_at'];
    public array $availableColumnsForMassUpdate = ['warehouse_id', 'product_id', 'quantity_in_stock', 'restock_threshold', 'last_restocked_at'];

    /**
     * ProductInventoryRepository constructor.
     *
     * @param ProductInventory $productInventory The ProductInventory model instance.
     */
    public function __construct(ProductInventory $productInventory)
    {
        parent::__construct($productInventory);
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