<?php

namespace App\Services\Warehouses;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Repos\Warehouses\WarehouseBinRepository;

class WarehouseBinService
{
    /**
     * Constructor to inject the WarehouseBinRepository dependency.
     */
    public function __construct(private WarehouseBinRepository $warehouseBinRepository) {}

    public function getAll(
        ?int $perPage = null,
        array $columns = ['*'],
        ?string $pageName = null,
        ?int $page = null,
        bool $withTrashed = false,
        bool $onlyTrashed = false,
        array $conditions = []
    ): Collection|LengthAwarePaginator {
        return $this->warehouseBinRepository->getAllUsingRepositoryBaseTrait(
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
        return $this->warehouseBinRepository->getByIdUsingRepositoryBaseTrait($id, $columns);
    }

    public function searchBy(string $field, mixed $value, array $columns = ['*']): ?object
    {
        return $this->warehouseBinRepository->searchByUsingRepositoryBaseTrait($field, $value, $columns);
    }

    public function create(array $data): ?object
    {
        return $this->warehouseBinRepository->createUsingRepositoryBaseTrait($data);
    }

    public function update(string $id, array $data, array $columns = ['*']): ?object
    {
        return $this->warehouseBinRepository->updateUsingRepositoryBaseTrait($id, $data, $columns);
    }

    public function delete(string $id, bool $force = false)
    {
        return $this->warehouseBinRepository->deleteUsingRepositoryBaseTrait($id, $force);
    }

}