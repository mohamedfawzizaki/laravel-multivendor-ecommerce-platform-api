<?php

namespace App\Repositories\Repos\Orders;

use App\Models\Currency;
use InvalidArgumentException;
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
        'code',
        'name',
        'symbol',
        'is_active',
        'is_base_currency',
        'exchange_rate',
    ];
    public array $availableColumns = [
        'code',
        'name',
        'symbol',
        'is_active',
        'is_base_currency',
        'exchange_rate',
    ];
    public array $availableConditionColumns = [
        'code',
        'name',
        'symbol',
        'is_active',
        'is_base_currency',
        'exchange_rate',
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

    public function getActive()
    {
        return $this->model->active()->get();
    }

    public function getBase()
    {
        return $this->model::base();
    }

    public function convertTo(Currency $targetCurrency, float $amount): float
    {
        $baseCurrency = $this->getBase();
        return $this->$baseCurrency->convertTo($targetCurrency, $amount);
    }

    public function convert(string $fromCode, string $toCode, float $amount): float
    {
        return $this->model::convert($fromCode, $toCode, $amount);
    }
}