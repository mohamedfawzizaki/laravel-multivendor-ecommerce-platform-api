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
        ?array $columns = null,
        ?string $pageName = null,
        ?int $page = null,
        bool $withTrashed = false,
        bool $onlyTrashed = false,
        ?array $conditions = null
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
    public function getUserById(string $id, ?array $columns = null): ?object
    {
        return $this->userRepository->getUserByIdUsingRepositoryBaseTrait($id, $columns);
    }

    public function searchBy(string $field, mixed $value, ?array $columns = null): ?object
    {
        return $this->userRepository->searchByUsingRepositoryBaseTrait($field, $value, $columns);
    }

    public function create(array $data): ?object
    {
        return $this->userRepository->createUsingRepositoryBaseTrait($data);
    }

    public function update(string $id, array $data): ?object
    {
        return $this->userRepository->updateUsingRepositoryBaseTrait($id, $data);
    }

    public function updateGroup(array $data, ?array $conditions = null, ?array $columns = null): Collection
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



































    














    // /**
    //  * Retrieves all users with optional pagination and column filtering.
    //  *
    //  * This method retrieves users from the repository, optionally paginated, and allows specifying
    //  * which columns to retrieve. If no specific columns are requested, it ensures that relationships
    //  * are loaded and hides pivot attributes for the specified relationships. If specific columns are
    //  * requested, it returns only those columns to avoid including relationship data.
    //  *
    //  * @param int|null $perPage The number of users per page. If null, all users are retrieved.
    //  * @param array|null $columns The columns to retrieve. If null, all columns are retrieved.
    //  * @param string|null $pageName The query parameter name for the page number.
    //  * @param int|null $page The current page number.
    //  * @return Collection|LengthAwarePaginator The retrieved users, with optional column filtering and hidden pivot attributes.
    //  */

    // public function getAllUsers(
    //     ?int $perPage = null,
    //     ?array $columns = null,
    //     ?string $pageName = null,
    //     ?int $page = null,
    //     bool $withTrashed = false,
    //     bool $onlyTrashed = false
    // ): Collection|LengthAwarePaginator {
    //     // Validate and filter the requested columns.
    //     // If no columns are provided, default to all columns (`['*']`).
    //     $validColumns = !$columns ? ['*'] : $this->userRepository->getValidColumns($columns);

    //     // Retrieve users with or without pagination based on the provided parameters.
    //     // If both `perPage` and `page` are provided, use pagination; otherwise, retrieve all users.
    //     $users = ($perPage && $page)
    //         ? $this->userRepository->paginate($perPage, $validColumns, $pageName, $page)
    //         : $this->userRepository->getAll($validColumns, $withTrashed, $onlyTrashed);

    //     // If no specific columns are requested, ensure relationships are loaded.
    //     if (!$columns) {
    //         // Get the relationships to load from the repository.
    //         $relationships = $this->userRepository->getRelationships();

    //         // Load missing relationships for each user if they are defined.
    //         if (!empty($relationships)) {
    //             foreach ($users as $user) {
    //                 $user->loadMissing($relationships);
    //             }
    //         }
    //     }

    //     // Hide pivot attributes from the retrieved users' relationships.
    //     $users = $this->userRepository->hidePivot($users, function ($user) {
    //         $this->userRepository->hideModelPivot($user, $this->userRepository->getRelationships());
    //     });

    //     // If specific columns are requested, return only those columns.
    //     // This avoids including relationship data in the response.
    //     // Otherwise, return the full user object.
    //     if (!$users->isEmpty()){
    //         return $columns ? $this->userRepository->getSpecificColumnsFromCollection($users, $validColumns) : $users;
    //     }
    //     // return empty collection:
    //     return $users;
    // }


    // /**
    //  * Retrieves a single user by their ID, optionally with specific columns and relationships.
    //  *
    //  * This method retrieves a user from the repository based on their ID. It allows specifying
    //  * which columns to retrieve and ensures that relationships are loaded if no specific columns
    //  * are requested. It also hides pivot attributes for the specified relationships.
    //  *
    //  * @param string $id The ID of the user to retrieve.
    //  * @param array|null $columns The columns to retrieve. If null, all columns are retrieved.
    //  * @return object|null The retrieved user, or null if the user is not found.
    //  */
    // public function getUserById(string $id, ?array $columns = null): ?object
    // {
    //     // Validate and filter the requested columns.
    //     // If no columns are provided, default to all columns (`['*']`).
    //     $validColumns = !$columns ? ['*'] : $this->userRepository->getValidColumns($columns);

    //     // Retrieve the user by ID with the specified columns.
    //     $user = $this->userRepository->findById($id, $validColumns);

    //     // If no specific columns are requested, ensure relationships are loaded.
    //     if (!$columns) {
    //         // Get the relationships to load from the repository.
    //         $relationships = $this->userRepository->getRelationships();

    //         // Load missing relationships if they are defined.
    //         if (!empty($relationships)) {
    //             $user->loadMissing($relationships);
    //         }
    //     }

    //     // Hide pivot attributes from the retrieved user's relationships.
    //     $this->userRepository->hideModelPivot($user, $this->userRepository->getRelationships());

    //     // If specific columns are requested, return only those columns.
    //     // to avoid appearance of the relationships data, else // Return the full user object.
    //     return $columns ? $this->userRepository->getSpecificColumnsFromSingleModel($user, $columns) : $user;
    // }

    // public function searchBy(string $field, mixed $value, ?array $columns = null): ?object
    // {
    //     // Validate and filter the requested columns.
    //     // If no columns are provided, default to all columns (`['*']`).
    //     $validColumns = !$columns ? ['*'] : $this->userRepository->getValidColumns($columns);

    //     $user = $this->userRepository->findByField($field, $value, $validColumns);

    //     // If no specific columns are requested, ensure relationships are loaded.
    //     if (!$columns) {
    //         // Get the relationships to load from the repository.
    //         $relationships = $this->userRepository->getRelationships();

    //         // Load missing relationships if they are defined.
    //         if (!empty($relationships)) {
    //             $user->loadMissing($relationships);
    //         }
    //     }

    //     // Hide pivot attributes from the retrieved user's relationships.
    //     $this->userRepository->hideModelPivot($user, $this->userRepository->getRelationships());

    //     // If specific columns are requested, return only those columns.
    //     // to avoid appearance of the relationships data, else // Return the full user object.
    //     return $columns ? $this->userRepository->getSpecificColumnsFromSingleModel($user, $columns) : $user;
    // }

    // public function create(array $data): ?object
    // {
    //     $data = $this->userRepository->prepareDataForMassAssignment($this->userRepository->getFillable(), $data);
    //     $user = $this->userRepository->create($data);

    //     // Get the relationships to load from the repository.
    //     $relationships = $this->userRepository->getRelationships();

    //     // Load missing relationships if they are defined.
    //     if (!empty($relationships)) {
    //         $user->loadMissing($relationships);
    //     }

    //     // Hide pivot attributes from the retrieved user's relationships.
    //     $this->userRepository->hideModelPivot($user, $this->userRepository->getRelationships());

    //     return $user;
    // }

    // public function update(string $id, array $data): ?object
    // {
    //     $data = $this->userRepository->prepareDataForMassAssignment($this->userRepository->getFillable(), $data);

    //     $user = $this->userRepository->update($id, $data);

    //     // Get the relationships to load from the repository.
    //     $relationships = $this->userRepository->getRelationships();

    //     // Load missing relationships if they are defined.
    //     if (!empty($relationships)) {
    //         $user->loadMissing($relationships);
    //     }

    //     // Hide pivot attributes from the retrieved user's relationships.
    //     $this->userRepository->hideModelPivot($user, $this->userRepository->getRelationships());

    //     return $user;
    // }

    // public function delete(string $id, bool $force = false)
    // {
    //     return $this->userRepository->delete($id, $force);
    // }
}