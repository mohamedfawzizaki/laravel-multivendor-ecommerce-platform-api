<?php

namespace App\Services;

use Illuminate\Support\Collection;
use App\Repositories\Repos\ContinentRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class ContinentService
{
    /**
     * Constructor to inject the ContinentRepository dependency.
     *
     * @param ContinentRepository $continentRepository The repository responsible for continent database operations.
     */
    public function __construct(private ContinentRepository $continentRepository) {}

    public function getAllContinents(
        ?int $perPage = null,
        array $columns = ['*'],
        ?string $pageName = null,
        ?int $page = null,
        bool $withTrashed = false,
        bool $onlyTrashed = false,
        array $conditions = []
    ): Collection|LengthAwarePaginator {
        return $this->continentRepository->getAllUsingRepositoryBaseTrait(
            $perPage,
            $columns,
            $pageName,
            $page,
            $withTrashed,
            $onlyTrashed,
            $conditions
        );
    }

    public function getContinentById(string $id, array $columns = ['*']): ?object
    {
        return $this->continentRepository->getByIdUsingRepositoryBaseTrait($id, $columns);
    }

    public function searchBy(string $field, mixed $value, array $columns = ['*']): ?object
    {
        return $this->continentRepository->searchByUsingRepositoryBaseTrait($field, $value, $columns);
    }

    public function create(array $data): ?object
    {
        return $this->continentRepository->createUsingRepositoryBaseTrait($data);
    }

    public function update(string $id, array $data, array $columns = ['*']): ?object
    {
        return $this->continentRepository->updateUsingRepositoryBaseTrait($id, $data, $columns);
    }

    public function delete(string $id, bool $force = false)
    {
        return $this->continentRepository->deleteUsingRepositoryBaseTrait($id, $force);
    }

    public function softDeleted(string $id)
    {
        return $this->continentRepository->softDeletedUsingRepositoryBaseTrait($id);
    }

    public function restore(string $id, array $columns = ['*'])
    {
        return $this->continentRepository->restoreUsingRepositoryBaseTrait($id, $columns);
    }
}