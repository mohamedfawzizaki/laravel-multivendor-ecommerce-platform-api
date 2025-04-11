<?php

namespace App\Services;

use Illuminate\Support\Collection;
use App\Repositories\Repos\CityRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class CityService
{
    /**
     * Constructor to inject the CityRepository dependency.
     *
     * @param CityRepository $cityRepository The repository responsible for city database operations.
     */
    public function __construct(private CityRepository $cityRepository) {}

    public function getAllCitys(
        ?int $perPage = null,
        array $columns = ['*'],
        ?string $pageName = null,
        ?int $page = null,
        bool $withTrashed = false,
        bool $onlyTrashed = false,
        array $conditions = []
    ): Collection|LengthAwarePaginator {
        return $this->cityRepository->getAllUsingRepositoryBaseTrait(
            $perPage,
            $columns,
            $pageName,
            $page,
            $withTrashed,
            $onlyTrashed,
            $conditions
        );
    }

    public function getCityById(string $id, array $columns = ['*']): ?object
    {
        return $this->cityRepository->getByIdUsingRepositoryBaseTrait($id, $columns);
    }

    public function searchBy(string $field, mixed $value, array $columns = ['*']): ?object
    {
        return $this->cityRepository->searchByUsingRepositoryBaseTrait($field, $value, $columns);
    }

    public function create(array $data): ?object
    {
        return $this->cityRepository->createUsingRepositoryBaseTrait($data);
    }

    public function update(string $id, array $data, array $columns = ['*']): ?object
    {
        return $this->cityRepository->updateUsingRepositoryBaseTrait($id, $data, $columns);
    }

    public function delete(string $id, bool $force = false)
    {
        return $this->cityRepository->deleteUsingRepositoryBaseTrait($id, $force);
    }

    public function softDeleted(string $id)
    {
        return $this->cityRepository->softDeletedUsingRepositoryBaseTrait($id);
    }

    public function restore(string $id, array $columns = ['*'])
    {
        return $this->cityRepository->restoreUsingRepositoryBaseTrait($id, $columns);
    }
}