<?php

namespace App\Repositories\Repos;

use App\Models\Permission;
use App\Repositories\EloquentBased\MainBaseRepository;
use App\Repositories\RepositoryPropertiesInterface;

class PermissionRepository extends MainBaseRepository implements RepositoryPropertiesInterface
{
    public array $relationships = [];
    public array $relationshipMap = [];
    public bool $hasPivot = false;
    public array $pivotWith = [];
    public array $defaultIDsForPivot = [];
    public array $fillable = ['name', 'description'];
    public array $availableColumns = ['name', 'description'];
    public array $availableConditionColumns = ['name', 'description'];
    public array $availableColumnsForMassUpdate = ['name', 'description'];

    public function __construct(Permission $user)
    {
        parent::__construct($user);
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