<?php

namespace App\Repositories\Eloquent;

use App\Repositories\RepositoryInterface;
use App\Repositories\RepositoryBaseTrait;
use App\Repositories\RepositoryHelperTrait;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Base repository class to handle common database operations.
 */
class BaseRepository implements RepositoryInterface
{
    use RepositoryBaseTrait, RepositoryHelperTrait;

    /**
     * Constructor to inject the model dependency.
     *
     * @param Model $model The Eloquent model instance.
     */
    public function __construct(protected Model $model) {}

    /**
     * Retrieves all records from the model.
     *
     * This method fetches all records from the database table associated with the model.
     * If no records are found, an empty collection is returned. If a database error occurs,
     * it logs the error and throws a `RuntimeException`. Similarly, any unexpected errors
     * are logged and rethrown as a `RuntimeException`.
     *
     * @return Collection The retrieved records. Returns an empty collection if no records are found.
     * @throws \RuntimeException If a database error or unexpected error occurs.
     */
    public function getAll(array $columns = ['*'], bool $withTrashed = false, bool $onlyTrashed = false, ?array $conditions = null): Collection
    {
        try {
            $query = $this->model->newQuery();

            if ($onlyTrashed) {
                $query = $this->model->onlyTrashed(); // Ensure it applies correctly
            } elseif ($withTrashed) {
                $query = $this->model->withTrashed();
            }

            // Apply conditions if provided
            $this->applyConditions($query, $conditions);

            return $query->get($columns);
        } catch (QueryException $queryException) {
            // Handle database-related errors (e.g., connection issues, SQL syntax errors).
            // Log the error with detailed context for debugging purposes.
            Log::error("Database error in " . get_class($this->model) . ": {$queryException->getMessage()}", [
                'model' => get_class($this->model), // The model class where the error occurred.
                'sql' => $queryException->getSql(), // The SQL query that caused the error.
                'bindings' => $queryException->getBindings(), // The bindings used in the query.
                'trace' => $queryException->getTraceAsString(), // The stack trace for debugging.
            ]);

            // Rethrow the exception as a `RuntimeException` with a user-friendly message.
            // This ensures the calling code can handle the error appropriately.
            throw new \RuntimeException("Database error while retrieving records", 500, $queryException);
        } catch (\Exception $exception) {
            // Handle any unexpected errors that are not related to the database.
            // Log the error with detailed context for debugging purposes.
            Log::error("Unexpected error in " . get_class($this->model) . ": {$exception->getMessage()}", [
                'model' => get_class($this->model), // The model class where the error occurred.
                'trace' => $exception->getTraceAsString(), // The stack trace for debugging.
            ]);

            // Rethrow the exception as a `RuntimeException` with a user-friendly message.
            // This ensures the calling code can handle the error appropriately.
            throw new \RuntimeException("Unexpected error while retrieving records", 500, $exception);
        }
    }

    /**
     * Paginates the records from the model.
     *
     * This method retrieves records from the model's associated table in a paginated format.
     * It allows customization of the number of records per page, the columns to retrieve,
     * the query parameter name for the page number, and the current page number.
     *
     * If a database error occurs, it logs the error and throws a `RuntimeException`.
     * Similarly, any unexpected errors are logged and rethrown as a `RuntimeException`.
     *
     * @param int $perPage The number of records to display per page. Defaults to 15.
     * @param array $columns The columns to retrieve. Defaults to all columns (`['*']`).
     * @param string $pageName The query parameter name for the page number. Defaults to 'page'.
     * @param int $page The current page number. Defaults to 0 (first page).
     * @return LengthAwarePaginator The paginated records.
     * @throws \RuntimeException If a database error or unexpected error occurs.
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], string $pageName = 'page', int $page = 1, bool $withTrashed = false, bool $onlyTrashed = false, ?array $conditions = null): LengthAwarePaginator
    {
        try {
            $query = $this->model->newQuery();

            if ($onlyTrashed) {
                $query->onlyTrashed();
            } elseif ($withTrashed) {
                $query->withTrashed();
            }

            // Apply conditions if provided
            $this->applyConditions($query, $conditions);

            $records = $query->paginate($perPage, $columns, $pageName, $page);
            return $records;
        } catch (QueryException $queryException) {
            // Handle database-related errors (e.g., connection issues, SQL syntax errors).
            // Log the error with detailed context for debugging purposes.
            Log::error("Database error during pagination in " . get_class($this->model) . ": {$queryException->getMessage()}", [
                'model' => get_class($this->model), // The model class where the error occurred.
                'sql' => $queryException->getSql(), // The SQL query that caused the error.
                'bindings' => $queryException->getBindings(), // The bindings used in the query.
                'trace' => $queryException->getTraceAsString(), // The stack trace for debugging.
            ]);

            // Rethrow the exception as a `RuntimeException` with a user-friendly message.
            // This ensures the calling code can handle the error appropriately.
            throw new \RuntimeException("Database error while paginating records", 500, $queryException);
        } catch (\Exception $exception) {
            // Handle any unexpected errors that are not related to the database.
            // Log the error with detailed context for debugging purposes.
            Log::error("Unexpected error during pagination in " . get_class($this->model) . ": {$exception->getMessage()}", [
                'model' => get_class($this->model), // The model class where the error occurred.
                'trace' => $exception->getTraceAsString(), // The stack trace for debugging.
            ]);

            // Rethrow the exception as a `RuntimeException` with a user-friendly message.
            // This ensures the calling code can handle the error appropriately.
            throw new \RuntimeException("Unexpected error while paginating records", 500, $exception);
        }
    }

    public function findById(int|string $id, array $columns = ['*']): ?Model
    {
        try {
            return $this->model->findOrFail($id, $columns);
        } catch (ModelNotFoundException $e) {
            Log::warning("Record not found in " . get_class($this->model) . " with ID: {$id}", [
                'model' => get_class($this->model),
                'id' => $id,
            ]);
            throw new \RuntimeException("Record not found", 404, $e);
        } catch (QueryException $queryException) {
            // Handle database-related errors (e.g., connection issues, SQL syntax errors).
            // Log the error with detailed context for debugging purposes.
            Log::error("Database error in " . get_class($this->model) . ": {$queryException->getMessage()}", [
                'model' => get_class($this->model), // The model class where the error occurred.
                'sql' => $queryException->getSql(), // The SQL query that caused the error.
                'bindings' => $queryException->getBindings(), // The bindings used in the query.
                'trace' => $queryException->getTraceAsString(), // The stack trace for debugging.
            ]);

            // Rethrow the exception as a `RuntimeException` with a user-friendly message.
            // This ensures the calling code can handle the error appropriately.
            throw new \RuntimeException("Database error while retrieving records", 500, $queryException);
        } catch (\Exception $exception) {
            // Handle any unexpected errors that are not related to the database.
            // Log the error with detailed context for debugging purposes.
            Log::error("Unexpected error in " . get_class($this->model) . ": {$exception->getMessage()}", [
                'model' => get_class($this->model), // The model class where the error occurred.
                'trace' => $exception->getTraceAsString(), // The stack trace for debugging.
            ]);

            // Rethrow the exception as a `RuntimeException` with a user-friendly message.
            // This ensures the calling code can handle the error appropriately.
            throw new \RuntimeException("Unexpected error while retrieving records", 500, $exception);
        }
    }

    public function findByField(string $field, mixed $value, array $columns = ['*']): ?Model
    {
        try {
            return $this->model->where($field, $value)->first($columns);
        } catch (ModelNotFoundException $e) {
            Log::warning("Record not found in " . get_class($this->model), [
                'model' => get_class($this->model),
            ]);
            throw new \RuntimeException("Record not found", 404, $e);
        } catch (QueryException $queryException) {
            // Handle database-related errors (e.g., connection issues, SQL syntax errors).
            // Log the error with detailed context for debugging purposes.
            Log::error("Database error in " . get_class($this->model) . ": {$queryException->getMessage()}", [
                'model' => get_class($this->model), // The model class where the error occurred.
                'sql' => $queryException->getSql(), // The SQL query that caused the error.
                'bindings' => $queryException->getBindings(), // The bindings used in the query.
                'trace' => $queryException->getTraceAsString(), // The stack trace for debugging.
            ]);

            // Rethrow the exception as a `RuntimeException` with a user-friendly message.
            // This ensures the calling code can handle the error appropriately.
            throw new \RuntimeException("Database error while retrieving records", 500, $queryException);
        } catch (\Exception $exception) {
            // Handle any unexpected errors that are not related to the database.
            // Log the error with detailed context for debugging purposes.
            Log::error("Unexpected error in " . get_class($this->model) . ": {$exception->getMessage()}", [
                'model' => get_class($this->model), // The model class where the error occurred.
                'trace' => $exception->getTraceAsString(), // The stack trace for debugging.
            ]);

            // Rethrow the exception as a `RuntimeException` with a user-friendly message.
            // This ensures the calling code can handle the error appropriately.
            throw new \RuntimeException("Unexpected error while retrieving records", 500, $exception);
        }
    }

    public function create(array $data): Model
    {
        try {
            return $this->model->create($data);
        } catch (QueryException $queryException) {
            // Handle database-related errors (e.g., connection issues, SQL syntax errors).
            // Log the error with detailed context for debugging purposes.
            Log::error("Database error in " . get_class($this->model) . ": {$queryException->getMessage()}", [
                'model' => get_class($this->model), // The model class where the error occurred.
                'sql' => $queryException->getSql(), // The SQL query that caused the error.
                'bindings' => $queryException->getBindings(), // The bindings used in the query.
                'trace' => $queryException->getTraceAsString(), // The stack trace for debugging.
            ]);

            // Rethrow the exception as a `RuntimeException` with a user-friendly message.
            // This ensures the calling code can handle the error appropriately.
            throw new \RuntimeException("Database error while creating records", 500, $queryException);
        } catch (\Exception $exception) {
            // Handle any unexpected errors that are not related to the database.
            // Log the error with detailed context for debugging purposes.
            Log::error("Unexpected error in " . get_class($this->model) . ": {$exception->getMessage()}", [
                'model' => get_class($this->model), // The model class where the error occurred.
                'trace' => $exception->getTraceAsString(), // The stack trace for debugging.
            ]);

            // Rethrow the exception as a `RuntimeException` with a user-friendly message.
            // This ensures the calling code can handle the error appropriately.
            throw new \RuntimeException("Unexpected error while creating records", 500, $exception);
        }
    }

    public function update(int|string $id, array $data): Model
    {
        try {

            $query = $this->model->newQuery()->withTrashed();

            $record = $query->findOrFail($id);

            $record->update($data); // Update the record with the new data
            return $record->refresh(); // Refresh the model to reflect the latest changes
        } catch (ModelNotFoundException $e) {
            Log::warning("Record not found in " . get_class($this->model) . " with ID: {$id}", [
                'model' => get_class($this->model),
                'id' => $id,
            ]);
            throw new \RuntimeException("Record not found", 404, $e);
        } catch (QueryException $queryException) {
            // Handle database-related errors (e.g., connection issues, SQL syntax errors).
            // Log the error with detailed context for debugging purposes.
            Log::error("Database error in " . get_class($this->model) . ": {$queryException->getMessage()}", [
                'model' => get_class($this->model), // The model class where the error occurred.
                'sql' => $queryException->getSql(), // The SQL query that caused the error.
                'bindings' => $queryException->getBindings(), // The bindings used in the query.
                'trace' => $queryException->getTraceAsString(), // The stack trace for debugging.
            ]);

            // Rethrow the exception as a `RuntimeException` with a user-friendly message.
            // This ensures the calling code can handle the error appropriately.
            throw new \RuntimeException("Database error while updating records", 500, $queryException);
        } catch (\Exception $exception) {
            // Handle any unexpected errors that are not related to the database.
            // Log the error with detailed context for debugging purposes.
            Log::error("Unexpected error in " . get_class($this->model) . ": {$exception->getMessage()}", [
                'model' => get_class($this->model), // The model class where the error occurred.
                'trace' => $exception->getTraceAsString(), // The stack trace for debugging.
            ]);

            // Rethrow the exception as a `RuntimeException` with a user-friendly message.
            // This ensures the calling code can handle the error appropriately.
            throw new \RuntimeException("Unexpected error while updating records", 500, $exception);
        }
    }

    public function updateGroup(array $data, ?array $conditions = null, ?array $columns = null): Collection
    {
        try {

            $query = $this->model->newQuery();

            // if ($onlyTrashed) {
            //     $query->onlyTrashed();
            // } elseif ($withTrashed) {
            //     $query->withTrashed();
            // }

            // Apply conditions if provided
            $this->applyConditions($query, $conditions);


            // Perform a bulk update
            $affectedRows = $query->update($data);

            // Fetch updated records if needed
            $records = $query->get();

            return $records;
        } catch (ModelNotFoundException $e) {
            Log::warning("Records not found in " . get_class($this->model), [
                'model' => get_class($this->model),
            ]);
            throw new \RuntimeException("Record not found", 404, $e);
        } catch (QueryException $queryException) {
            // Handle database-related errors (e.g., connection issues, SQL syntax errors).
            // Log the error with detailed context for debugging purposes.
            Log::error("Database error in " . get_class($this->model) . ": {$queryException->getMessage()}", [
                'model' => get_class($this->model), // The model class where the error occurred.
                'sql' => $queryException->getSql(), // The SQL query that caused the error.
                'bindings' => $queryException->getBindings(), // The bindings used in the query.
                'trace' => $queryException->getTraceAsString(), // The stack trace for debugging.
            ]);

            // Rethrow the exception as a `RuntimeException` with a user-friendly message.
            // This ensures the calling code can handle the error appropriately.
            throw new \RuntimeException("Database error while updating records", 500, $queryException);
        } catch (\Exception $exception) {
            // Handle any unexpected errors that are not related to the database.
            // Log the error with detailed context for debugging purposes.
            Log::error("Unexpected error in " . get_class($this->model) . ": {$exception->getMessage()}", [
                'model' => get_class($this->model), // The model class where the error occurred.
                'trace' => $exception->getTraceAsString(), // The stack trace for debugging.
            ]);

            // Rethrow the exception as a `RuntimeException` with a user-friendly message.
            // This ensures the calling code can handle the error appropriately.
            throw new \RuntimeException("Unexpected error while updating records", 500, $exception);
        }
    }

    public function delete(int|string $id, bool $force = false): bool
    {
        try {
            $record = $this->model->newQuery()->withTrashed()->find($id);

            if (!$record) {
                throw new ModelNotFoundException("Record with ID {$id} not found.");
            }

            return $force ? (bool) $record->forceDelete() : (bool) $record->delete();
        } catch (ModelNotFoundException $e) {
            Log::warning("Record not found in " . get_class($this->model) . " with ID: {$id}", [
                'model' => get_class($this->model),
                'id' => $id,
            ]);
            throw new \RuntimeException("Record not found", 404, $e);
        } catch (QueryException $queryException) {
            // Handle database-related errors (e.g., connection issues, SQL syntax errors).
            // Log the error with detailed context for debugging purposes.
            Log::error("Database error in " . get_class($this->model) . ": {$queryException->getMessage()}", [
                'model' => get_class($this->model), // The model class where the error occurred.
                'sql' => $queryException->getSql(), // The SQL query that caused the error.
                'bindings' => $queryException->getBindings(), // The bindings used in the query.
                'trace' => $queryException->getTraceAsString(), // The stack trace for debugging.
            ]);

            // Rethrow the exception as a `RuntimeException` with a user-friendly message.
            // This ensures the calling code can handle the error appropriately.
            throw new \RuntimeException("Database error while deleting records", 500, $queryException);
        } catch (\Exception $exception) {
            // Handle any unexpected errors that are not related to the database.
            // Log the error with detailed context for debugging purposes.
            Log::error("Unexpected error in " . get_class($this->model) . ": {$exception->getMessage()}", [
                'model' => get_class($this->model), // The model class where the error occurred.
                'trace' => $exception->getTraceAsString(), // The stack trace for debugging.
            ]);

            // Rethrow the exception as a `RuntimeException` with a user-friendly message.
            // This ensures the calling code can handle the error appropriately.
            throw new \RuntimeException("Unexpected error while deleting records", 500, $exception);
        }
    }

    public function deleteBulkRecords(array $conditions, bool $force = false): int
    {
        try {
            $query = $this->model->newQuery()->withTrashed();

            // Apply conditions if provided
            $this->applyConditions($query, $conditions);

            // Perform deletion and ensure it always returns an integer
            return (int) ($force ? $query->forceDelete() : $query->delete());
        } catch (ModelNotFoundException $e) {
            Log::warning("Record not found in " . get_class($this->model), [
                'model' => get_class($this->model),
            ]);
            throw new \RuntimeException("Record not found", 404, $e);
        } catch (QueryException $queryException) {
            // Handle database-related errors (e.g., connection issues, SQL syntax errors).
            // Log the error with detailed context for debugging purposes.
            Log::error("Database error in " . get_class($this->model) . ": {$queryException->getMessage()}", [
                'model' => get_class($this->model), // The model class where the error occurred.
                'sql' => $queryException->getSql(), // The SQL query that caused the error.
                'bindings' => $queryException->getBindings(), // The bindings used in the query.
                'trace' => $queryException->getTraceAsString(), // The stack trace for debugging.
            ]);

            // Rethrow the exception as a `RuntimeException` with a user-friendly message.
            // This ensures the calling code can handle the error appropriately.
            throw new \RuntimeException("Database error while deleting records", 500, $queryException);
        } catch (\Exception $exception) {
            // Handle any unexpected errors that are not related to the database.
            // Log the error with detailed context for debugging purposes.
            Log::error("Unexpected error in " . get_class($this->model) . ": {$exception->getMessage()}", [
                'model' => get_class($this->model), // The model class where the error occurred.
                'trace' => $exception->getTraceAsString(), // The stack trace for debugging.
            ]);

            // Rethrow the exception as a `RuntimeException` with a user-friendly message.
            // This ensures the calling code can handle the error appropriately.
            throw new \RuntimeException("Unexpected error while deleting records", 500, $exception);
        }
    }




    // {}

    // public function where(array $conditions, array $columns = ['*']): Collection
    // {}
    // public function firstWhere(array $conditions, array $columns = ['*']): ?Model
    // {}

    // public function countWhere(array $conditions): int
    // {}
    // public function existsWhere(array $conditions): bool
    // {}





















    // /**
    //  * Deletes a record by ID.
    //  *
    //  * @param int|string $id The ID of the model to delete.
    //  * @return bool True if the deletion was successful, false otherwise.
    //  */
    // public function delete(int|string $id): bool
    // {
    //     // Fetch the record by ID
    //     $record = $this->findById($id);

    //     // If the record does not exist, return false
    //     if (!$record) {
    //         return false;
    //     }

    //     try {
    //         return (bool) $record->delete(); // Delete the record and return success status
    //     } catch (Exception $e) {
    //         // Log the error for debugging
    //         Log::error("Error deleting record in " . get_class($this->model), [
    //             'error' => $e->getMessage(),
    //             'id' => $id
    //         ]);
    //         return false; // Return false to indicate failure
    //     }
    // }
}