<?php

namespace App\Repositories\Repos\Products;

use App\Models\Order;
use App\Repositories\RepositoryPropertiesInterface;
use App\Repositories\EloquentBased\MainBaseRepository;

class OrderRepository extends MainBaseRepository implements RepositoryPropertiesInterface
{
    public array $relationships = [];

    public array $relationshipMap = [];
    public bool $hasPivot = false;
    public array $pivotWith = [];
    public array $defaultIDsForPivot = [];
    public array $fillable = [
        'user_id',
        'subtotal',
        'tax',
        'total_price',
        'status',
        'order_number'
    ];
    public array $availableColumns = [
        'id',
        'user_id',
        'subtotal',
        'tax',
        'total_price',
        'status',
    ];
    public array $availableConditionColumns = [
        'id',
        'user_id',
        'subtotal',
        'tax',
        'total_price',
        'status',
    ];
    public array $availableColumnsForMassUpdate = [
        'subtotal',
        'tax',
        'total_price',
        'status',
    ];

    /**
     * OrderRepository constructor.
     *
     * @param Order $order The Order model instance.
     */
    public function __construct(Order $order)
    {
        parent::__construct($order);
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