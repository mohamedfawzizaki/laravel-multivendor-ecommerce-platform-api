<?php

namespace App\Repositories;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

trait RepositoryBaseTrait
{
    /**
     * Retrieves all records with optional pagination and column filtering.
     *
     * This method retrieves records from the repository, optionally paginated, and allows specifying
     * which columns to retrieve. If no specific columns are requested, it ensures that relationships
     * are loaded and hides pivot attributes for the specified relationships. If specific columns are
     * requested, it returns only those columns to avoid including relationship data.
     *
     * @param int|null $perPage The number of records per page. If null, all records are retrieved.
     * @param array|null $columns The columns to retrieve. If null, all columns are retrieved.
     * @param string|null $pageName The query parameter name for the page number.
     * @param int|null $page The current page number.
     * @return Collection|LengthAwarePaginator The retrieved records, with optional column filtering and hidden pivot attributes.
     */
    public function getAllUsersUsingRepositoryBaseTrait(
        ?int $perPage = null,
        ?array $columns = null,
        ?string $pageName = null,
        ?int $page = null,
        bool $withTrashed = false,
        bool $onlyTrashed = false,
        ?array $conditions = null
    ): Collection|LengthAwarePaginator {
        // Validate and filter the requested columns.
        // If no columns are provided, default to all columns (`['*']`).
        $validColumns = !$columns ? ['*'] : $this->getValidColumns($columns);

        // Retrieve records with or without pagination based on the provided parameters.
        // If both `perPage` and `page` are provided, use pagination; otherwise, retrieve all records.
        $records = ($perPage && $page)
            ? $this->paginate($perPage, $validColumns, $pageName, $page, $withTrashed, $onlyTrashed, $conditions)
            : $this->getAll($validColumns, $withTrashed, $onlyTrashed, $conditions);

        // If no specific columns are requested, ensure relationships are loaded.
        if (!$columns) {
            // Get the relationships to load from the repository.
            $relationships = $this->getRelationships();

            // Load missing relationships for each record if they are defined.
            if (!empty($relationships)) {
                foreach ($records as $record) {
                    $record->loadMissing($relationships);
                }
            }
        }

        // Hide pivot attributes from the retrieved records' relationships.
        $records = $this->hidePivot($records, function ($record) {
            $this->hideModelPivot($record, $this->getRelationships());
        });

        // If specific columns are requested, return only those columns.
        // This avoids including relationship data in the response.
        // Otherwise, return the full record object.
        if (!$records->isEmpty()) {
            return $columns ? $this->getSpecificColumnsFromCollection($records, $validColumns) : $records;
        }
        // return empty collection:
        return $records;
    }

    /**
     * Retrieves a single record by their ID, optionally with specific columns and relationships.
     *
     * This method retrieves a record from the repository based on their ID. It allows specifying
     * which columns to retrieve and ensures that relationships are loaded if no specific columns
     * are requested. It also hides pivot attributes for the specified relationships.
     *
     * @param string $id The ID of the record to retrieve.
     * @param array|null $columns The columns to retrieve. If null, all columns are retrieved.
     * @return object|null The retrieved record, or null if the record is not found.
     */
    public function getUserByIdUsingRepositoryBaseTrait(string $id, ?array $columns = null): ?object
    {
        // Validate and filter the requested columns.
        // If no columns are provided, default to all columns (`['*']`).
        $validColumns = !$columns ? ['*'] : $this->getValidColumns($columns);

        // Retrieve the record by ID with the specified columns.
        $record = $this->findById($id, $validColumns);

        // If no specific columns are requested, ensure relationships are loaded.
        if (!$columns) {
            // Get the relationships to load from the repository.
            $relationships = $this->getRelationships();

            // Load missing relationships if they are defined.
            if (!empty($relationships)) {
                $record->loadMissing($relationships);
            }
        }

        // Hide pivot attributes from the retrieved record's relationships.
        $this->hideModelPivot($record, $this->getRelationships());

        // If specific columns are requested, return only those columns.
        // to avoid appearance of the relationships data, else // Return the full record object.
        return $columns ? $this->getSpecificColumnsFromSingleModel($record, $columns) : $record;
    }

    public function searchByUsingRepositoryBaseTrait(string $field, mixed $value, ?array $columns = null): ?object
    {
        // Validate and filter the requested columns.
        // If no columns are provided, default to all columns (`['*']`).
        $validColumns = !$columns ? ['*'] : $this->getValidColumns($columns);

        $record = $this->findByField($field, $value, $validColumns);

        // If no specific columns are requested, ensure relationships are loaded.
        if (!$columns) {
            // Get the relationships to load from the repository.
            $relationships = $this->getRelationships();

            // Load missing relationships if they are defined.
            if (!empty($relationships)) {
                $record->loadMissing($relationships);
            }
        }

        // Hide pivot attributes from the retrieved record's relationships.
        $this->hideModelPivot($record, $this->getRelationships());

        // If specific columns are requested, return only those columns.
        // to avoid appearance of the relationships data, else // Return the full record object.
        return $columns ? $this->getSpecificColumnsFromSingleModel($record, $columns) : $record;
    }

    public function createUsingRepositoryBaseTrait(array $data): ?object
    {
        $data = $this->prepareDataForMassAssignment($this->getFillable(), $data);
        $record = $this->create($data);

        // Get the relationships to load from the repository.
        $relationships = $this->getRelationships();

        // Load missing relationships if they are defined.
        if (!empty($relationships)) {
            $record->loadMissing($relationships);
        }

        // Hide pivot attributes from the retrieved record's relationships.
        $this->hideModelPivot($record, $this->getRelationships());

        return $record;
    }

    public function updateUsingRepositoryBaseTrait(string $id, array $data): ?object
    {
        $data = $this->prepareDataForMassAssignment($this->getFillable(), $data);

        $record = $this->update($id, $data);

        // Get the relationships to load from the repository.
        $relationships = $this->getRelationships();

        // Load missing relationships if they are defined.
        if (!empty($relationships)) {
            $record->loadMissing($relationships);
        }

        // Hide pivot attributes from the retrieved record's relationships.
        $this->hideModelPivot($record, $this->getRelationships());

        return $record;
    }

    public function updateGroupUsingRepositoryBaseTrait(array $data, ?array $conditions = null, ?array $columns = null): Collection
    {
        // Validate and filter the requested columns.
        // If no columns are provided, default to all columns (`['*']`).
        $validColumns = !$columns ? ['*'] : $this->getValidColumns($columns);

        $data = $this->prepareDataForMassAssignment($this->getAvailableColumnsForMassUpdate(), $data);

        $records = $this->updateGroup($data, $conditions);

        // If no specific columns are requested, ensure relationships are loaded.
        if (!$columns) {
            // Get the relationships to load from the repository.
            $relationships = $this->getRelationships();

            // Load missing relationships for each record if they are defined.
            if (!empty($relationships)) {
                foreach ($records as $record) {
                    $record->loadMissing($relationships);
                }
            }
        }

        // Hide pivot attributes from the retrieved records' relationships.
        $records = $this->hidePivot($records, function ($record) {
            $this->hideModelPivot($record, $this->getRelationships());
        });

        // If specific columns are requested, return only those columns.
        // This avoids including relationship data in the response.
        // Otherwise, return the full record object.
        if (!$records->isEmpty()) {
            return $columns ? $this->getSpecificColumnsFromCollection($records, $validColumns) : $records;
        }
        // return empty collection:
        return $records;
    }

    public function deleteUsingRepositoryBaseTrait(string $id, bool $force = false)
    {
        return $this->delete($id, $force);
    }

    public function deleteBulkUsingRepositoryBaseTrait(array $conditions, bool $force = false)
    {
        return $this->deleteBulkRecords($conditions, $force);
    }

























    /**
     * Restores a soft-deleted record by its ID.
     *
     * @param int|string $id The unique identifier of the model.
     * @return bool True if the restoration was successful, false otherwise.
     */
    public function restore(int|string $id): bool
    {
        try {
            $record = $this->model->onlyTrashed()->find($id);

            if (!$record) {
                Log::warning("Restore failed: Record not found or not trashed (ID: {$id})");
                return false;
            }

            if (!$record->restore()) {
                Log::warning("Restore failed: Unable to restore record (ID: {$id})");
                return false;
            }

            Log::info("Record restored successfully (ID: {$id})");
            return true;
        } catch (Exception $e) {
            Log::error("Restore error (ID: {$id}): " . $e->getMessage());
            return false;
        }
    }
}