<?php

namespace App\Repositories\Repos\Products;

use App\Models\Brand;
use App\Repositories\RepositoryPropertiesInterface;
use App\Repositories\EloquentBased\MainBaseRepository;

class BrandRepository extends MainBaseRepository implements RepositoryPropertiesInterface
{
    // public array $relationships = ['products'];
    public array $relationships = [];

    public array $relationshipMap = [];
    public bool $hasPivot = false;
    public array $pivotWith = [];
    public array $defaultIDsForPivot = [];
    public array $fillable = [
        'name',
        // 'slug',
        'description',
        'logo_url',
        'website_url',
    ];
    public array $availableColumns = [
        'name',
        // 'slug',
        'description',
        'logo_url',
        'website_url',
    ];
    public array $availableConditionColumns = ['name', 'created_at'];
    public array $availableColumnsForMassUpdate = ['website_url'];

    /**
     * BrandRepository constructor.
     *
     * @param Brand $brand The Brand model instance.
     */
    public function __construct(Brand $brand)
    {
        parent::__construct($brand);
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