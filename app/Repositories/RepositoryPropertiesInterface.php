<?php

namespace App\Repositories;

interface RepositoryPropertiesInterface
{
    public function getRelationships(): array;

    public function getRelationshipKeys(): array;

    public function getFillable(): array;

    public function getAvailableColumns(): array;

    public function getAvailableConditionColumns(): array;

    public function getAvailableColumnsForMassUpdate(): array;
}