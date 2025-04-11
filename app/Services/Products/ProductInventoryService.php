<?php

namespace App\Services\Products;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Repos\Products\ProductInventoryRepository;

class ProductInventoryService
{
    /**
     * Constructor to inject the ProductInventoryRepository dependency.
     *
     * @param \App\Repositories\Repos\Products\ProductInventoryRepository $productInventoryRepository
     */
    public function __construct(private ProductInventoryRepository $productInventoryRepository) {}

    public function getAllProductInventories(
        ?int $perPage = null,
        array $columns = ['*'],
        ?string $pageName = null,
        ?int $page = null,
        bool $withTrashed = false,
        bool $onlyTrashed = false,
        array $conditions = []
    ): Collection|LengthAwarePaginator {
        return $this->productInventoryRepository->getAllUsingRepositoryBaseTrait(
            $perPage,
            $columns,
            $pageName,
            $page,
            $withTrashed,
            $onlyTrashed,
            $conditions
        );
    }

    public function getProductInventoryById(string $id, array $columns = ['*']): ?object
    {
        return $this->productInventoryRepository->getByIdUsingRepositoryBaseTrait($id, $columns);
    }

    public function searchBy(string $field, mixed $value, array $columns = ['*']): ?object
    {
        return $this->productInventoryRepository->searchByUsingRepositoryBaseTrait($field, $value, $columns);
    }

    public function create(array $data): ?object
    {
        return $this->productInventoryRepository->createUsingRepositoryBaseTrait($data);
    }

    public function update(string $id, array $data, array $columns = ['*']): ?object
    {
        return $this->productInventoryRepository->updateUsingRepositoryBaseTrait($id, $data, $columns);
    }

    public function updateGroup(array $data, array $conditions = [], array $columns = ['*']): Collection
    {
        return $this->productInventoryRepository->updateGroupUsingRepositoryBaseTrait($data, $conditions, $columns);
    }

    public function delete(string $id, bool $force = false)
    {
        return $this->productInventoryRepository->deleteUsingRepositoryBaseTrait($id, $force);
    }

    public function deleteBulk(array $conditions, bool $force = false)
    {
        return $this->productInventoryRepository->deleteBulkUsingRepositoryBaseTrait($conditions, $force);
    }
}