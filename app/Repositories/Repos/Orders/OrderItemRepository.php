<?php

namespace App\Repositories\Repos\Products;

use App\Models\OrderItem;
use App\Repositories\RepositoryPropertiesInterface;
use App\Repositories\EloquentBased\MainBaseRepository;

class OrderItemRepository extends MainBaseRepository implements RepositoryPropertiesInterface
{
    // public array $relationships = ['user', 'product', 'variant'];
    public array $relationships = [];

    public array $relationshipMap = [];
    public bool $hasPivot = false;
    public array $pivotWith = [];
    public array $defaultIDsForPivot = [];
    public array $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'quantity',
        'price',
    ];
    public array $availableColumns = [
        'id',
        'order_id',
        'product_id',
        'variant_id',
        'quantity',
        'price',
    ];
    public array $availableConditionColumns = [
        'id',
        'order_id',
        'product_id',
        'variant_id',
        'quantity',
        'price',
    ];
    public array $availableColumnsForMassUpdate = [
        'quantity',
        'price',
    ];

    /**
     * OrderItemRepository constructor.
     *
     * @param OrderItem $orderItem The OrderItem model instance.
     */
    public function __construct(OrderItem $orderItem)
    {
        parent::__construct($orderItem);
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