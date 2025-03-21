<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use Illuminate\Support\Collection;
use App\Repositories\RepositoryHelperTrait;
use App\Repositories\Eloquent\BaseRepository;
use App\Repositories\RepositoryDeletionTrait;
use App\Repositories\RepositoryPropertiesInterface;

/**
 * UserRepository extends BaseRepository and provides
 * additional user-specific database operations.
 */
class UserRepository extends BaseRepository implements RepositoryPropertiesInterface
{
    // use RepositoryDeletionTrait, RepositoryHelperTrait;

    public array $relationships = ['role.permissions'];
    public array $relationshipKeys = ['role_id', 'status_id'];
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

    public function getRelationshipKeys(): array
    {
        return $this->relationshipKeys;
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