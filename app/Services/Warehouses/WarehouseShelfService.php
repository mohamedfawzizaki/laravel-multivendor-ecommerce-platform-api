<?php

namespace App\Services\Warehouses;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Repos\Warehouses\WarehouseZoneRepository;

class WarehouseShelfService
{
    /**
     * Constructor to inject the WarehouseZoneRepository dependency.
     */
    public function __construct(private WarehouseZoneRepository $warehouseZoneRepository) {}

    public function getAll(
        ?int $perPage = null,
        array $columns = ['*'],
        ?string $pageName = null,
        ?int $page = null,
        bool $withTrashed = false,
        bool $onlyTrashed = false,
        array $conditions = []
    ): Collection|LengthAwarePaginator {
        return $this->warehouseZoneRepository->getAllUsingRepositoryBaseTrait(
            $perPage,
            $columns,
            $pageName,
            $page,
            $withTrashed,
            $onlyTrashed,
            $conditions
        );
    }

    public function getById(string $id, array $columns = ['*']): ?object
    {
        return $this->warehouseZoneRepository->getByIdUsingRepositoryBaseTrait($id, $columns);
    }

    public function searchBy(string $field, mixed $value, array $columns = ['*']): ?object
    {
        return $this->warehouseZoneRepository->searchByUsingRepositoryBaseTrait($field, $value, $columns);
    }

    public function create(array $data): ?object
    {
        return $this->warehouseZoneRepository->createUsingRepositoryBaseTrait($data);
    }

    public function update(string $id, array $data, array $columns = ['*']): ?object
    {
        return $this->warehouseZoneRepository->updateUsingRepositoryBaseTrait($id, $data, $columns);
    }

    public function delete(string $id, bool $force = false)
    {
        return $this->warehouseZoneRepository->deleteUsingRepositoryBaseTrait($id, $force);
    }

}