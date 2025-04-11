<?php

namespace App\Repositories\Repos;

use App\Models\Vendor;
use App\Repositories\EloquentBased\MainBaseRepository;
use App\Repositories\RepositoryPropertiesInterface;

class VendorRepository extends MainBaseRepository implements RepositoryPropertiesInterface
{
    public array $relationships = ['user'];
    public array $relationshipMap = [
        'user' => 'user_id',
    ];
    public bool $hasPivot = false;
    public array $pivotWith = [];
    public array $defaultIDsForPivot = [];
    public array $fillable = ['user_id', 'business_description', 'business_name', 'documentation_url', 'logo_url', 'status', 'approved_at'];
    public array $availableColumns = ['id', 'user_id', 'business_description', 'business_name', 'documentation_url', 'logo_url', 'status', 'created_at', 'updated_at', 'deleted_at', 'approved_at'];
    public array $availableConditionColumns =  ['id', 'user_id', 'business_description', 'business_name', 'documentation_url', 'logo_url', 'status', 'created_at', 'updated_at', 'deleted_at', 'approved_at'];
    public array $availableColumnsForMassUpdate = ['user_id', 'documentation_url', 'logo_url', 'status', 'status', 'approved_at'];

    /**
     * VendorRepository constructor.
     *
     * @param Vendor $vendor The Vendor model instance.
     */
    public function __construct(Vendor $vendor)
    {
        parent::__construct($vendor);
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