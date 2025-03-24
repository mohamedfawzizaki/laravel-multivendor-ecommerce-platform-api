<?php

namespace App\Services;

use Illuminate\Support\Collection;
use App\Repositories\Repos\RoleRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class RoleService
{
    /**
     * Constructor to inject the RoleRepository dependency.
     *
     * @param RoleRepository $roleRepository The repository responsible for role database operations.
     */
    public function __construct(private RoleRepository $roleRepository) {}

    public function getAllRoles(
        ?int $perPage = null,
        array $columns = ['*'],
        ?string $pageName = null,
        ?int $page = null,
        bool $withTrashed = false,
        bool $onlyTrashed = false,
        array $conditions = []
    ): Collection|LengthAwarePaginator {
        return $this->roleRepository->getAllUsingRepositoryBaseTrait(
            $perPage,
            $columns,
            $pageName,
            $page,
            $withTrashed,
            $onlyTrashed,
            $conditions
        );
    }

    public function getRoleById(string $id, array $columns = ['*']): ?object
    {
        return $this->roleRepository->getByIdUsingRepositoryBaseTrait($id, $columns);
    }

    public function create(array $data): ?object
    {
        return $this->roleRepository->createUsingRepositoryBaseTrait($data);
    }

    public function update(string $id, array $data, array $columns = ['*']): ?object
    {
        return $this->roleRepository->updateUsingRepositoryBaseTrait($id, $data, $columns);
    }

    public function delete(string $id, bool $force = false)
    {
        return $this->roleRepository->deleteUsingRepositoryBaseTrait($id, $force);
    }

    public function softDeleted(string $id)
    {
        return $this->roleRepository->softDeletedUsingRepositoryBaseTrait($id);
    }

    public function restore(string $id, array $columns = ['*'])
    {
        return $this->roleRepository->restoreUsingRepositoryBaseTrait($id, $columns);
    }
}