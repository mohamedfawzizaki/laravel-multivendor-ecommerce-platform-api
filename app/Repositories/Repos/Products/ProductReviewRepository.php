<?php

namespace App\Repositories\Repos\Products;

use App\Models\ProductReview;
use App\Repositories\RepositoryPropertiesInterface;
use App\Repositories\EloquentBased\MainBaseRepository;

class ProductReviewRepository extends MainBaseRepository implements RepositoryPropertiesInterface
{
    // public array $relationships = ['product'];
    public array $relationships = [];

    public array $relationshipMap = [];
    public bool $hasPivot = false;
    public array $pivotWith = [];
    public array $defaultIDsForPivot = [];
    public array $fillable = [
        'product_id',
        'user_id',
        'review',
        'rating',
        'verified_purchase',
    ];
    public array $availableColumns = [
        'id',
        'product_id',
        'user_id',
        'review',
        'rating',
        'verified_purchase',
    ];
    public array $availableConditionColumns = [
        'id',
        'product_id',
        'user_id',
        'review',
        'rating',
        'verified_purchase',
    ];
    public array $availableColumnsForMassUpdate = [
        'review',
        'rating',
        'verified_purchase',
    ];

    /**
     * ImageRepository constructor.
     *
     * @param ProductReview $productVariant The ProductReview model instance.
     */
    public function __construct(ProductReview $productReview)
    {
        parent::__construct($productReview);
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