<?php

namespace App\Repositories\Repos\Products;

use App\Models\Products\ProductDiscount;
use App\Repositories\RepositoryPropertiesInterface;
use App\Repositories\EloquentBased\MainBaseRepository;

class ProductDiscountRepository extends MainBaseRepository implements RepositoryPropertiesInterface
{
    // public array $relationships = ['product'];
    public array $relationships = [];

    public array $relationshipMap = [];
    public bool $hasPivot = false;
    public array $pivotWith = [];
    public array $defaultIDsForPivot = [];
    public array $fillable = [
        'product_id',
        'discount_price',
        'discount_percentage',
        'start_date',
        'end_date',
    ];
    public array $availableColumns = [
        'id',
        'product_id',
        'discount_price',
        'discount_percentage',
        'start_date',
        'end_date',
    ];
    public array $availableConditionColumns = [
        'id',
        'product_id',
        'discount_price',
        'discount_percentage',
        'start_date',
        'end_date',
    ];
    public array $availableColumnsForMassUpdate = [
        'discount_price',
        'discount_percentage',
        'start_date',
        'end_date',
    ];

    /**
     * 
     *
     * @param ProductDiscount $ProductDiscount The ProductDiscount model instance.
     */
    public function __construct(ProductDiscount $productDiscount)
    {
        parent::__construct($productDiscount);
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