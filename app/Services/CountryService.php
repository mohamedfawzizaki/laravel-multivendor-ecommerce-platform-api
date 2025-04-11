<?php

namespace App\Services;

use Illuminate\Support\Collection;
use App\Repositories\Repos\CountryRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class CountryService
{
    /**
     * Constructor to inject the CountryRepository dependency.
     *
     * @param CountryRepository $countryRepository The repository responsible for country database operations.
     */
    public function __construct(private CountryRepository $countryRepository) {}

    public function getAllCountrys(
        ?int $perPage = null,
        array $columns = ['*'],
        ?string $pageName = null,
        ?int $page = null,
        bool $withTrashed = false,
        bool $onlyTrashed = false,
        array $conditions = []
    ): Collection|LengthAwarePaginator {
        return $this->countryRepository->getAllUsingRepositoryBaseTrait(
            $perPage,
            $columns,
            $pageName,
            $page,
            $withTrashed,
            $onlyTrashed,
            $conditions
        );
    }

    public function getCountryById(string $id, array $columns = ['*']): ?object
    {
        return $this->countryRepository->getByIdUsingRepositoryBaseTrait($id, $columns);
    }

    public function searchBy(string $field, mixed $value, array $columns = ['*']): ?object
    {
        return $this->countryRepository->searchByUsingRepositoryBaseTrait($field, $value, $columns);
    }

    public function create(array $data): ?object
    {
        return $this->countryRepository->createUsingRepositoryBaseTrait($data);
    }

    public function update(string $id, array $data, array $columns = ['*']): ?object
    {
        return $this->countryRepository->updateUsingRepositoryBaseTrait($id, $data, $columns);
    }

    public function delete(string $id, bool $force = false)
    {
        return $this->countryRepository->deleteUsingRepositoryBaseTrait($id, $force);
    }

    public function softDeleted(string $id)
    {
        return $this->countryRepository->softDeletedUsingRepositoryBaseTrait($id);
    }

    public function restore(string $id, array $columns = ['*'])
    {
        return $this->countryRepository->restoreUsingRepositoryBaseTrait($id, $columns);
    }
}