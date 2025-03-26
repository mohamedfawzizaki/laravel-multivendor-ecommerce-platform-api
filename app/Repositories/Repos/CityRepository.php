<?php

namespace App\Repositories\Repos;

use App\Models\City;
use App\Repositories\RepositoryPropertiesInterface;
use App\Repositories\EloquentBased\MainBaseRepository;

class CityRepository extends MainBaseRepository implements RepositoryPropertiesInterface
{
    public array $relationships = [];
    public array $relationshipMap = [
        'user' => 'user_id',
    ];
    public bool $hasPivot = false;
    public array $pivotWith = [];
    public array $defaultIDsForPivot = [];
    public array $fillable = ['name', 'user_id'];
    public array $availableColumns = ['name', 'user_id', 'address_line', 'created_at'];
    public array $availableConditionColumns = ['name','user_id', 'address_line', 'created_at'];
    public array $availableColumnsForMassUpdate = ['name', 'address_line', 'user_id'];

    /**
     * CityRepository constructor.
     *
     * @param City $address The City model instance.
     */
    public function __construct(City $address)
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