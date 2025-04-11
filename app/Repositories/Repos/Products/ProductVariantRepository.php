<?php

namespace App\Repositories\Repos\Products;

use App\Models\ProductVariant;
use App\Repositories\RepositoryPropertiesInterface;
use App\Repositories\EloquentBased\MainBaseRepository;

class ProductVariantRepository extends MainBaseRepository implements RepositoryPropertiesInterface
{
    // public array $relationships = ['product'];
    public array $relationships = [];

    public array $relationshipMap = [];
    public bool $hasPivot = false;
    public array $pivotWith = [];
    public array $defaultIDsForPivot = [];
    public array $fillable = [
        'product_id',
        'variant_name',
        'price',
        'stock',
        'sku',
        'attributes',
    ];
    public array $availableColumns = [
        'id',
        'product_id',
        'variant_name',
        'price',
        'stock',
        'sku',
        'attributes',
    ];
    public array $availableConditionColumns = [
        'id',
        'product_id',
        'variant_name',
        'price',
        'stock',
        'sku',
        'attributes',
    ];
    public array $availableColumnsForMassUpdate = [
        'variant_name',
        'price',
        'stock',
        'sku',
        'attributes',
    ];

    /**
     * ImageRepository constructor.
     *
     * @param ProductVariant $productVariant The ProductVariant model instance.
     */
    public function __construct(ProductVariant $productVariant)
    {
        parent::__construct($productVariant);
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