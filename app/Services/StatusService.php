<?php

namespace App\Services;

use Illuminate\Support\Collection;
use App\Repositories\Repos\StatusRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class StatusService
{
    /**
     * Constructor to inject the StatusRepository dependency.
     *
     * @param StatusRepository $statusRepository The repository responsible for status database operations.
     */
    public function __construct(private StatusRepository $statusRepository) {}

    public function getAllStatuss(
        ?int $perPage = null,
        array $columns = ['*'],
        ?string $pageName = null,
        ?int $page = null,
        bool $withTrashed = false,
        bool $onlyTrashed = false,
        array $conditions = []
    ): Collection|LengthAwarePaginator {
        return $this->statusRepository->getAllUsingRepositoryBaseTrait(
            $perPage,
            $columns,
            $pageName,
            $page,
            $withTrashed,
            $onlyTrashed,
            $conditions
        );
    }

    public function getStatusById(string $id, array $columns = ['*']): ?object
    {
        return $this->statusRepository->getByIdUsingRepositoryBaseTrait($id, $columns);
    }

    public function create(array $data): ?object
    {
        return $this->statusRepository->createUsingRepositoryBaseTrait($data);
    }

    public function update(string $id, array $data, array $columns = ['*']): ?object
    {
        return $this->statusRepository->updateUsingRepositoryBaseTrait($id, $data, $columns);
    }

    public function delete(string $id, bool $force = false)
    {
        return $this->statusRepository->deleteUsingRepositoryBaseTrait($id, $force);
    }

    public function softDeleted(string $id)
    {
        return $this->statusRepository->softDeletedUsingRepositoryBaseTrait($id);
    }

    public function restore(string $id, array $columns = ['*'])
    {
        return $this->statusRepository->restoreUsingRepositoryBaseTrait($id, $columns);
    }
}