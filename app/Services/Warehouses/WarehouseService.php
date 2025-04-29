<?php

namespace App\Services\Warehouses;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Repos\Warehouses\WarehouseRepository;

class WarehouseService
{
    /**
     * Constructor to inject the WarehouseRepository dependency.
     */
    public function __construct(private WarehouseRepository $warehouseRepository) {}

    public function getAll(
        ?int $perPage = null,
        array $columns = ['*'],
        ?string $pageName = null,
        ?int $page = null,
        bool $withTrashed = false,
        bool $onlyTrashed = false,
        array $conditions = []
    ): Collection|LengthAwarePaginator {
        return $this->warehouseRepository->getAllUsingRepositoryBaseTrait(
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
        return $this->warehouseRepository->getByIdUsingRepositoryBaseTrait($id, $columns);
    }

    public function searchBy(string $field, mixed $value, array $columns = ['*']): ?object
    {
        return $this->warehouseRepository->searchByUsingRepositoryBaseTrait($field, $value, $columns);
    }

    public function create(array $data): ?object
    {
        return $this->warehouseRepository->createUsingRepositoryBaseTrait($data);
    }

    public function update(string $id, array $data, array $columns = ['*']): ?object
    {
        return $this->warehouseRepository->updateUsingRepositoryBaseTrait($id, $data, $columns);
    }

    public function delete(string $id, bool $force = false)
    {
        return $this->warehouseRepository->deleteUsingRepositoryBaseTrait($id, $force);
    }

}