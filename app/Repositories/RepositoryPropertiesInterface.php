<?php

namespace App\Repositories;

interface RepositoryPropertiesInterface
{
    public function getRelationships(): array;

    public function getRelationshipMap(): array;

    public function hasPivot(): bool;

    public function getPivotWith(): array;

    public function getDefualtIDsForPivot(): array;

    public function getFillable(): array;

    public function getAvailableColumns(): array;

    public function getAvailableConditionColumns(): array;

    public function getAvailableColumnsForMassUpdate(): array;
}