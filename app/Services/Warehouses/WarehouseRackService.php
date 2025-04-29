<?php

namespace App\Services\Warehouses;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Repos\Warehouses\WarehouseRackRepository;

class WarehouseRackService
{
    /**
     * Constructor to inject the WarehouseRackRepository dependency.
     */
    public function __construct(private WarehouseRackRepository $warehouseRackRepository) {}

    public function getAll(
        ?int $perPage = null,
        array $columns = ['*'],
        ?string $pageName = null,
        ?int $page = null,
        bool $withTrashed = false,
        bool $onlyTrashed = false,
        array $conditions = []
    ): Collection|LengthAwarePaginator {
        return $this->warehouseRackRepository->getAllUsingRepositoryBaseTrait(
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
        return $this->warehouseRackRepository->getByIdUsingRepositoryBaseTrait($id, $columns);
    }

    public function searchBy(string $field, mixed $value, array $columns = ['*']): ?object
    {
        return $this->warehouseRackRepository->searchByUsingRepositoryBaseTrait($field, $value, $columns);
    }

    public function create(array $data): ?object
    {
        return $this->warehouseRackRepository->createUsingRepositoryBaseTrait($data);
    }

    public function update(string $id, array $data, array $columns = ['*']): ?object
    {
        return $this->warehouseRackRepository->updateUsingRepositoryBaseTrait($id, $data, $columns);
    }

    public function delete(string $id, bool $force = false)
    {
        return $this->warehouseRackRepository->deleteUsingRepositoryBaseTrait($id, $force);
    }

}