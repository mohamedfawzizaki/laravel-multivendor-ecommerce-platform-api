<?php

namespace App\Services;

use Illuminate\Support\Collection;
use App\Repositories\Repos\PermissionRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class PermissionService
{
    /**
     * Constructor to inject the PermissionRepository dependency.
     *
     * @param PermissionRepository $permissionRepository The repository responsible for permission database operations.
     */
    public function __construct(private PermissionRepository $permissionRepository) {}

    public function getAllPermissions(
        ?int $perPage = null,
        array $columns = ['*'],
        ?string $pageName = null,
        ?int $page = null,
        bool $withTrashed = false,
        bool $onlyTrashed = false,
        array $conditions = []
    ): Collection|LengthAwarePaginator {
        return $this->permissionRepository->getAllUsingRepositoryBaseTrait(
            $perPage,
            $columns,
            $pageName,
            $page,
            $withTrashed,
            $onlyTrashed,
            $conditions
        );
    }

    public function getPermissionById(string $id, array $columns = ['*']): ?object
    {
        return $this->permissionRepository->getByIdUsingRepositoryBaseTrait($id, $columns);
    }

    public function create(array $data): ?object
    {
        return $this->permissionRepository->createUsingRepositoryBaseTrait($data);
    }

    public function update(string $id, array $data, array $columns = ['*']): ?object
    {
        return $this->permissionRepository->updateUsingRepositoryBaseTrait($id, $data, $columns);
    }

    public function delete(string $id, bool $force = false)
    {
        return $this->permissionRepository->deleteUsingRepositoryBaseTrait($id, $force);
    }

    public function softDeleted(string $id)
    {
        return $this->permissionRepository->softDeletedUsingRepositoryBaseTrait($id);
    }

    public function restore(string $id, array $columns = ['*'])
    {
        return $this->permissionRepository->restoreUsingRepositoryBaseTrait($id, $columns);
    }
}