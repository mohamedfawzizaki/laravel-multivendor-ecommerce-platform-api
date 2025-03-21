<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * RepositoryInterface defines the common operations 
 * that should be implemented by all repository classes.
 */
interface RepositoryInterface
{
    /**
     * Retrieves all records from the database.
     *
     * @return Collection A collection of all model instances.
     */
    public function getAll(): Collection;

    /**
     * Retrieves paginated records from the database.
     *
     * @param int $perPage The number of records per page.
     * @param array $columns The columns to retrieve.
     * @param string $pageName The query string variable used to store the page.
     * @param int|null $page The current page number.
     * @return LengthAwarePaginator The paginated collection of model instances.
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], string $pageName = 'page', int $page = 0): LengthAwarePaginator;

    /**
     * Finds a specific record by its ID.
     *
     * @param int|string $id The unique identifier of the model.
     * @param array $columns The columns to retrieve.
     * @return Model|null The model instance if found, or null if not found.
     */
    public function findById(int|string $id, array $columns = ['*']): ?Model;

    /**
     * Finds a specific record by a given field and value.
     *
     * @param string $field The field to search by.
     * @param mixed $value The value to search for.
     * @param array $columns The columns to retrieve.
     * @return Model|null The model instance if found, or null if not found.
     */
    public function findByField(string $field, mixed $value, array $columns = ['*']): ?Model;

    /**
     * Creates a new record in the database.
     *
     * @param array $data The data used to create a new model instance.
     * @return Model The created model instance.
     * @throws \Exception If the creation fails.
     */
    public function create(array $data): Model;

    /**
     * Updates a record by ID.
     *
     * @param int|string $id The unique identifier of the model to update.
     * @param array $data The updated data for the record.
     * @return Model The updated model instance.
     * @throws \Exception If the update fails.
     */
    public function update(int|string $id, array $data): Model;

    /**
     * Deletes a record by ID.
     *
     * @param int|string $id The unique identifier of the model to delete.
     * @return bool True if the deletion was successful, false otherwise.
     * @throws \Exception If the deletion fails.
     */
    public function delete(int|string $id, bool $force = false): bool;

    /**
     * Retrieves records based on a set of conditions.
     *
     * @param array $conditions The conditions to filter by.
     * @param array $columns The columns to retrieve.
     * @return Collection A collection of model instances that match the conditions.
     */
    // public function where(array $conditions, array $columns = ['*']): Collection;

    /**
     * Retrieves the first record that matches the given conditions.
     *
     * @param array $conditions The conditions to filter by.
     * @param array $columns The columns to retrieve.
     * @return Model|null The first model instance that matches the conditions, or null if not found.
     */
    // public function firstWhere(array $conditions, array $columns = ['*']): ?Model;

    /**
     * Counts the number of records that match the given conditions.
     *
     * @param array $conditions The conditions to filter by.
     * @return int The number of records that match the conditions.
     */
    // public function countWhere(array $conditions): int;

    /**
     * Checks if a record exists based on the given conditions.
     *
     * @param array $conditions The conditions to filter by.
     * @return bool True if at least one record exists that matches the conditions, false otherwise.
     */
    // public function existsWhere(array $conditions): bool;
}