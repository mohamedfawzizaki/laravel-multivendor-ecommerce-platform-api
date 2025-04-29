<?php

namespace App\Repositories\Repos\Orders;

use App\Models\OrderPayment;
use App\Repositories\RepositoryPropertiesInterface;
use App\Repositories\EloquentBased\MainBaseRepository;

class OrderPaymentRepository extends MainBaseRepository implements RepositoryPropertiesInterface
{
    // public array $relationships = ['user', 'product', 'variant'];
    public array $relationships = [];

    public array $relationshipMap = [];
    public bool $hasPivot = false;
    public array $pivotWith = [];
    public array $defaultIDsForPivot = [];
    public array $fillable = [
        'order_id', 'method', 'status', 'transaction_id', 'amount', 'currency_code', 'processed_at', 'gateway_response'
    ];
    public array $availableColumns = [
        'id','order_id', 'method', 'status', 'transaction_id', 'amount', 'currency_code', 'processed_at', 'gateway_response'
    ];
    public array $availableConditionColumns = [
        'id','order_id', 'method', 'status', 'transaction_id', 'amount', 'currency_code', 'processed_at', 'gateway_response'
    ];
    public array $availableColumnsForMassUpdate = [
        'method', 'status', 'amount', 'currency_code'
    ];

    /**
     * OrderPaymentRepository constructor.
     *
     * @param OrderPayment $orderPayment The OrderPayment model instance.
     */
    public function __construct(OrderPayment $orderPayment)
    {
        parent::__construct($orderPayment);
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