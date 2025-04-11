<?php

namespace App\Repositories\Repos\Products;

use App\Models\Currency;
use App\Repositories\RepositoryPropertiesInterface;
use App\Repositories\EloquentBased\MainBaseRepository;

class CurrencyRepository extends MainBaseRepository implements RepositoryPropertiesInterface
{
    public array $relationships = [];

    public array $relationshipMap = [];
    public bool $hasPivot = false;
    public array $pivotWith = [];
    public array $defaultIDsForPivot = [];
    public array $fillable = [
        'code', 'name', 'symbol', 'exchange_rate'
    ];
    public array $availableColumns = [
        'id','code', 'name', 'symbol', 'exchange_rate'
    ];
    public array $availableConditionColumns = [
        'id','code', 'name', 'symbol', 'exchange_rate'
    ];
    public array $availableColumnsForMassUpdate = [];

    /**
     * CurrencyRepository constructor.
     *
     * @param Currency $currency The Currency model instance.
     */
    public function __construct(Currency $currency)
    {
        parent::__construct($currency);
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