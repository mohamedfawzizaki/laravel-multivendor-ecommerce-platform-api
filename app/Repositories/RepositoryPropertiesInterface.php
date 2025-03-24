<?php

namespace App\Repositories;

interface RepositoryPropertiesInterface
{
    public function getRelationships(): array;

    // public function getRelationshipKeys(): array; // each foreign key should be in parallel with its aliasis.

    // public function getRelationshipKeysAliasis(): array;
    public function getRelationshipMap(): array;

    public function getFillable(): array;

    public function getAvailableColumns(): array;

    public function getAvailableConditionColumns(): array;

    public function getAvailableColumnsForMassUpdate(): array;
}