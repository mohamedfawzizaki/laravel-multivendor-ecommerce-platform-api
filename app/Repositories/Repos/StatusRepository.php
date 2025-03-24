<?php

namespace App\Repositories\Repos;

use App\Models\Status;
use App\Repositories\EloquentBased\MainBaseRepository;
use App\Repositories\RepositoryPropertiesInterface;


class StatusRepository extends MainBaseRepository implements RepositoryPropertiesInterface
{
    public array $relationships = [];
    public array $relationshipMap = [];
    public array $fillable = ['name', 'description'];
    public array $availableColumns = ['id', 'name', 'description'];
    public array $availableConditionColumns = ['name', 'description'];
    public array $availableColumnsForMassUpdate = ['name', 'description'];

    public function __construct(Status $status)
    {
        parent::__construct($status);
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
}