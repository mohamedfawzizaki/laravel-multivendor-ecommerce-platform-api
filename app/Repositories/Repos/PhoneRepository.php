<?php

namespace App\Repositories\Repos;

use App\Models\Phone;
use App\Repositories\EloquentBased\MainBaseRepository;
use App\Repositories\RepositoryPropertiesInterface;

class PhoneRepository extends MainBaseRepository implements RepositoryPropertiesInterface
{
    public array $relationships = [];
    public array $relationshipMap = [
        'user' => 'user_id',
    ];
    public bool $hasPivot = false;
    public array $pivotWith = [];
    public array $defaultIDsForPivot = [];
    public array $fillable = ['phone', 'is_primary', 'user_id'];
    public array $availableColumns = ['id', 'phone', 'is_primary', 'user_id', 'created_at'];
    public array $availableConditionColumns = ['id', 'phone', 'is_primary', 'user_id', 'created_at'];
    public array $availableColumnsForMassUpdate = ['phone', 'is_primary'];

    /**
     * PhoneRepository constructor.
     *
     * @param Phone $phone The Phone model instance.
     */
    public function __construct(Phone $phone)
    {
        parent::__construct($phone);
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