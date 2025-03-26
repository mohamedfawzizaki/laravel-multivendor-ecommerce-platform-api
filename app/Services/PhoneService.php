<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Repositories\Repos\PhoneRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class PhoneService
{
    /**
     * Constructor to inject the PhoneRepository dependency.
     *
     * @param PhoneRepository $phoneRepository The repository responsible for phone database operations.
     */
    public function __construct(private PhoneRepository $phoneRepository) {}

    public function getAllPhones(
        ?int $perPage = null,
        array $columns = ['*'],
        ?string $pageName = null,
        ?int $page = null,
        bool $withTrashed = false,
        bool $onlyTrashed = false,
        array $conditions = []
    ): Collection|LengthAwarePaginator {
        return $this->phoneRepository->getAllUsingRepositoryBaseTrait(
            $perPage,
            $columns,
            $pageName,
            $page,
            $withTrashed,
            $onlyTrashed,
            $conditions
        );
    }

    public function getPhoneById(string $id, array $columns = ['*']): ?Model
    {
        return $this->phoneRepository->getByIdUsingRepositoryBaseTrait($id, $columns);
    }

    public function searchBy(string $field, mixed $value, array $columns = ['*']): ?object
    {
        return $this->phoneRepository->searchByUsingRepositoryBaseTrait($field, $value, $columns);
    }

    public function create(array $data): ?object
    {
        return $this->phoneRepository->createUsingRepositoryBaseTrait($data);
    }

    public function update(string $id, array $data, array $columns = ['*']): ?object
    {
        return $this->phoneRepository->updateUsingRepositoryBaseTrait($id, $data, $columns);
    }

    public function updateGroup(array $data, array $conditions = [], array $columns = ['*']): Collection
    {
        return $this->phoneRepository->updateGroupUsingRepositoryBaseTrait($data, $conditions, $columns);
    }

    public function delete(string $id, bool $force = false)
    {
        return $this->phoneRepository->deleteUsingRepositoryBaseTrait($id, $force);
    }

    public function deleteBulk(array $conditions, bool $force = false)
    {
        return $this->phoneRepository->deleteBulkUsingRepositoryBaseTrait($conditions, $force);
    }

    public function softDeleted(string $id)
    {
        return $this->phoneRepository->softDeletedUsingRepositoryBaseTrait($id);
    }

    public function restore(string $id, array $columns = ['*'])
    {
        return $this->phoneRepository->restoreUsingRepositoryBaseTrait($id, $columns);
    }

    public function restoreBulk(array $conditions = [], array $columns = ['*'])
    {
        return $this->phoneRepository->restoreBulkUsingRepositoryBaseTrait($conditions, $columns);
    }
}