<?php

namespace App\Repositories\Repos;

use App\Models\User;
use App\Repositories\EloquentBased\MainBaseRepository;
use App\Repositories\RepositoryPropertiesInterface;

class UserRepository extends MainBaseRepository implements RepositoryPropertiesInterface
{
    public array $relationships = ['role.permissions', 'status', 'phone', 'addresses'];
    public array $relationshipMap = [
        'role' => 'role_id',
        'status' => 'status_id',
    ];
    public bool $hasPivot = false;
    public array $pivotWith = [];
    public array $defaultIDsForPivot = [];
    public array $fillable = ['name', 'email', 'password', 'role_id'];
    public array $availableColumns = ['id', 'name', 'email', 'role_id', 'status_id', 'created_at'];
    public array $availableConditionColumns = ['id', 'name', 'email', 'role_id', 'status_id', 'created_at'];
    public array $availableColumnsForMassUpdate = ['password', 'role_id', 'status_id', 'created_at'];

    /**
     * UserRepository constructor.
     *
     * @param User $user The User model instance.
     */
    public function __construct(User $user)
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