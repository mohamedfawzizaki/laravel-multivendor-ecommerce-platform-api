<?php

namespace App\Repositories\Repos\Products;

use App\Models\Image;
use App\Repositories\RepositoryPropertiesInterface;
use App\Repositories\EloquentBased\MainBaseRepository;

class ImageRepository extends MainBaseRepository implements RepositoryPropertiesInterface
{
    // public array $relationships = ['products'];
    public array $relationships = [];

    public array $relationshipMap = [];
    public bool $hasPivot = false;
    public array $pivotWith = [];
    public array $defaultIDsForPivot = [];
    public array $fillable = [
        'product_id',
        'image_url',
        'is_primary',
    ];
    public array $availableColumns = [
        'id',
        'product_id',
        'image_url',
        'is_primary',
    ];
    public array $availableConditionColumns = [
        'id',
        'product_id',
        'image_url',
        'is_primary',
    ];
    public array $availableColumnsForMassUpdate = [];

    /**
     * ImageRepository constructor.
     *
     * @param Image $brand The Image model instance.
     */
    public function __construct(Image $brand)
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