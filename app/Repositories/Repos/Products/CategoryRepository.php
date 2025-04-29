<?php

namespace App\Repositories\Repos\Products;

use App\Models\Products\Category;
use App\Repositories\RepositoryPropertiesInterface;
use App\Repositories\EloquentBased\MainBaseRepository;

class CategoryRepository extends MainBaseRepository implements RepositoryPropertiesInterface
{
    // public array $relationships = ['products'];
    public array $relationships = [];
    public array $relationshipMap = [];
    public bool $hasPivot = false;
    public array $pivotWith = [];
    public array $defaultIDsForPivot = [];
    public array $fillable = [
        'name',
        'slug',
        'description',
    ];
    public array $availableColumns = [
        'name',
        'slug',
        'description',
    ];
    public array $availableConditionColumns = [
        'name',
        'slug',
        'description',
    ];
    public array $availableColumnsForMassUpdate = [];

    /**
     * CategoryRepository constructor.
     *
     * @param Category $category The Category model instance.
     */
    public function __construct(Category $category)
    {
        parent::__construct($category);
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