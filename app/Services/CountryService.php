<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Repositories\Repos\CountryRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class CountryService
{
    /**
     * Constructor to inject the CountryRepository dependency.
     *
     * @param CountryRepository $addressRepository The repository responsible for address database operations.
     */
    public function __construct(private CountryRepository $addressRepository) {}

    public function getAllCountrys(
        ?int $perPage = null,
        array $columns = ['*'],
        ?string $pageName = null,
        ?int $page = null,
        bool $withTrashed = false,
        bool $onlyTrashed = false,
        array $conditions = []
    ): Collection|LengthAwarePaginator {
        return $this->addressRepository->getAllUsingRepositoryBaseTrait(
            $perPage,
            $columns,
            $pageName,
            $page,
            $withTrashed,
            $onlyTrashed,
            $conditions
        );
    }

    public function getCountryById(string $id, array $columns = ['*']): ?Model
    {
        return $this->addressRepository->getByIdUsingRepositoryBaseTrait($id, $columns);
    }

    public function searchBy(string $field, mixed $value, array $columns = ['*']): ?object
    {
        return $this->addressRepository->searchByUsingRepositoryBaseTrait($field, $value, $columns);
    }

    public function create(array $data): ?object
    {
        return $this->addressRepository->createUsingRepositoryBaseTrait($data);
    }

    public function update(string $id, array $data, array $columns = ['*']): ?object
    {
        return $this->addressRepository->updateUsingRepositoryBaseTrait($id, $data, $columns);
    }

    public function updateGroup(array $data, array $conditions = [], array $columns = ['*']): Collection
    {
        return $this->addressRepository->updateGroupUsingRepositoryBaseTrait($data, $conditions, $columns);
    }

    public function delete(string $id, bool $force = false)
    {
        return $this->addressRepository->deleteUsingRepositoryBaseTrait($id, $force);
    }

    public function deleteBulk(array $conditions, bool $force = false)
    {
        return $this->addressRepository->deleteBulkUsingRepositoryBaseTrait($conditions, $force);
    }

    public function softDeleted(string $id)
    {
        return $this->addressRepository->softDeletedUsingRepositoryBaseTrait($id);
    }

    public function restore(string $id, array $columns = ['*'])
    {
        return $this->addressRepository->restoreUsingRepositoryBaseTrait($id, $columns);
    }

    public function restoreBulk(array $conditions = [], array $columns = ['*'])
    {
        return $this->addressRepository->restoreBulkUsingRepositoryBaseTrait($conditions, $columns);
    }
}