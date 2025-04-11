<?php

namespace App\Services\Products;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Repos\Products\OrderItemRepository;

class OrderItemService
{
    /**
     * Constructor to inject the OrderItemRepository dependency.
     *
     * @param \App\Repositories\Repos\Products\OrderItemRepository $orderItemRepository
     */
    public function __construct(private OrderItemRepository $orderItemRepository) {}

    public function getAllOrderItems(
        ?int $perPage = null,
        array $columns = ['*'],
        ?string $pageName = null,
        ?int $page = null,
        bool $withTrashed = false,
        bool $onlyTrashed = false,
        array $conditions = []
    ): Collection|LengthAwarePaginator {
        return $this->orderItemRepository->getAllUsingRepositoryBaseTrait(
            $perPage,
            $columns,
            $pageName,
            $page,
            $withTrashed,
            $onlyTrashed,
            $conditions
        );
    }

    public function getOrderItemById(string $id, array $columns = ['*']): ?object
    {
        return $this->orderItemRepository->getByIdUsingRepositoryBaseTrait($id, $columns);
    }

    public function searchBy(string $field, mixed $value, array $columns = ['*']): ?object
    {
        return $this->orderItemRepository->searchByUsingRepositoryBaseTrait($field, $value, $columns);
    }

    public function create(array $data): ?object
    {
        return $this->orderItemRepository->createUsingRepositoryBaseTrait($data);
    }

    public function update(string $id, array $data, array $columns = ['*']): ?object
    {
        return $this->orderItemRepository->updateUsingRepositoryBaseTrait($id, $data, $columns);
    }

    public function updateGroup(array $data, array $conditions = [], array $columns = ['*']): Collection
    {
        return $this->orderItemRepository->updateGroupUsingRepositoryBaseTrait($data, $conditions, $columns);
    }

    public function delete(string $id, bool $force = false)
    {
        return $this->orderItemRepository->deleteUsingRepositoryBaseTrait($id, $force);
    }

    public function deleteBulk(array $conditions, bool $force = false)
    {
        return $this->orderItemRepository->deleteBulkUsingRepositoryBaseTrait($conditions, $force);
    }

    public function softDeleted(string $id)
    {
        return $this->orderItemRepository->softDeletedUsingRepositoryBaseTrait($id);
    }

    public function restore(string $id, array $columns = ['*'])
    {
        return $this->orderItemRepository->restoreUsingRepositoryBaseTrait($id, $columns);
    }

    public function restoreBulk(array $conditions = [], array $columns = ['*'])
    {
        return $this->orderItemRepository->restoreBulkUsingRepositoryBaseTrait($conditions, $columns);
    }
}