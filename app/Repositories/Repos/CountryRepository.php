<?php

namespace App\Repositories\Repos;

use App\Models\Country;
use App\Repositories\RepositoryPropertiesInterface;
use App\Repositories\EloquentBased\MainBaseRepository;

class CountryRepository extends MainBaseRepository implements RepositoryPropertiesInterface
{
    public array $relationships = ['continent'];
    public array $relationshipMap = [
        'continent' => 'continent_id',
    ];
    public bool $hasPivot = false;
    public array $pivotWith = [];
    public array $defaultIDsForPivot = [];
    public array $fillable = ['name', 'continent_id'];
    public array $availableColumns = ['id', 'name', 'continent_id', 'created_at'];
    public array $availableConditionColumns = ['id', 'name', 'continent_id', 'created_at'];
    public array $availableColumnsForMassUpdate = ['name', 'continent_id',];

    /**
     * CountryRepository constructor.
     *
     * @param Country $address The Country model instance.
     */
    public function __construct(Country $address)
    {
        parent::__construct($address);
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