<?php

namespace App\Repositories\Repos\Products;

use App\Models\Wishlist;
use App\Repositories\RepositoryPropertiesInterface;
use App\Repositories\EloquentBased\MainBaseRepository;

class WishlistRepository extends MainBaseRepository implements RepositoryPropertiesInterface
{
    // public array $relationships = ['user', 'product', 'variant'];
    public array $relationships = [];

    public array $relationshipMap = [];
    public bool $hasPivot = false;
    public array $pivotWith = [];
    public array $defaultIDsForPivot = [];
    public array $fillable = [
        'user_id',
        'session_id',
        'wishlist_name',
        'variant_id',
        'product_id',
        'notes',
        'notify_preferences',
        'expires_at',
    ];
    public array $availableColumns = [
        'id',
        'user_id',
        'session_id',
        'wishlist_name',
        'variant_id',
        'product_id',
        'notes',
        'notify_preferences',
        'expires_at',
    ];
    public array $availableConditionColumns = [
        'id',
        'user_id',
        'session_id',
        'wishlist_name',
        'variant_id',
        'product_id',
        'notes',
        'notify_preferences',
        'expires_at',
    ];
    public array $availableColumnsForMassUpdate = [];

    /**
     * WishlistRepository constructor.
     *
     * @param Wishlist $cart The Wishlist model instance.
     */
    public function __construct(Wishlist $cart)
    {
        parent::__construct($cart);
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