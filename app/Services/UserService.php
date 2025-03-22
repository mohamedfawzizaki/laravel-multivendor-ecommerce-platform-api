<?php

namespace App\Services;

use Illuminate\Support\Collection;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{
    /**
     * Constructor to inject the UserRepository dependency.
     *
     * @param UserRepository $userRepository The repository responsible for user database operations.
     */
    public function __construct(private UserRepository $userRepository) {}

    /**
     * Retrieves all users with optional pagination and column filtering.
     *
     * This method retrieves users from the repository, optionally paginated, and allows specifying
     * which columns to retrieve. If no specific columns are requested, it ensures that relationships
     * are loaded and hides pivot attributes for the specified relationships. If specific columns are
     * requested, it returns only those columns to avoid including relationship data.
     *
     * @param int|null $perPage The number of users per page. If null, all users are retrieved.
     * @param array|null $columns The columns to retrieve. If null, all columns are retrieved.
     * @param string|null $pageName The query parameter name for the page number.
     * @param int|null $page The current page number.
     * @return Collection|LengthAwarePaginator The retrieved users, with optional column filtering and hidden pivot attributes.
     */
    public function getAllUsers(
        ?int $perPage = null,
        array $columns = ['*'],
        ?string $pageName = null,
        ?int $page = null,
        bool $withTrashed = false,
        bool $onlyTrashed = false,
        array $conditions = []
    ): Collection|LengthAwarePaginator {
        return $this->userRepository->getAllUsersUsingRepositoryBaseTrait(
            $perPage,
            $columns,
            $pageName,
            $page,
            $withTrashed,
            $onlyTrashed,
            $conditions
        );
    }

    /**
     * Retrieves a single user by their ID, optionally with specific columns and relationships.
     *
     * This method retrieves a user from the repository based on their ID. It allows specifying
     * which columns to retrieve and ensures that relationships are loaded if no specific columns
     * are requested. It also hides pivot attributes for the specified relationships.
     *
     * @param string $id The ID of the user to retrieve.
     * @param array|null $columns The columns to retrieve. If null, all columns are retrieved.
     * @return object|null The retrieved user, or null if the user is not found.
     */
    public function getUserById(string $id, array $columns = ['*']): ?object
    {
        return $this->userRepository->getUserByIdUsingRepositoryBaseTrait($id, $columns);
    }

    public function searchBy(string $field, mixed $value, array $columns = ['*']): ?object
    {
        return $this->userRepository->searchByUsingRepositoryBaseTrait($field, $value, $columns);
    }

    public function create(array $data): ?object
    {
        return $this->userRepository->createUsingRepositoryBaseTrait($data);
    }

    public function update(string $id, array $data, array $columns = ['*']): ?object
    {
        return $this->userRepository->updateUsingRepositoryBaseTrait($id, $data, $columns);
    }

    public function updateGroup(array $data, array $conditions = [], array $columns = ['*']): Collection
    {
        return $this->userRepository->updateGroupUsingRepositoryBaseTrait($data, $conditions, $columns);
    }

    public function delete(string $id, bool $force = false)
    {
        return $this->userRepository->deleteUsingRepositoryBaseTrait($id, $force);
    }

    public function deleteBulk(array $conditions, bool $force = false)
    {
        return $this->userRepository->deleteBulkUsingRepositoryBaseTrait($conditions, $force);
    }

    public function softDeleted(string $id)
    {
        return $this->userRepository->softDeletedUsingRepositoryBaseTrait($id);
    }

    public function restore(string $id, array $columns = ['*'])
    {
        return $this->userRepository->restoreUsingRepositoryBaseTrait($id, $columns);
    }

    public function restoreBulk(array $conditions = [], array $columns = ['*'])
    {
        return $this->userRepository->restoreBulkUsingRepositoryBaseTrait($conditions, $columns);
    }





}