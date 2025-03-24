<?php

namespace App\Repositories\EloquentBased;

use InvalidArgumentException;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use App\Repositories\RepositoryBaseTrait;
use App\Repositories\RepositoryInterface;
use App\Repositories\RepositoryHelperTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class MainBaseRepository implements RepositoryInterface
{
    use RepositoryBaseTrait, RepositoryHelperTrait;
    use BaseRepositoryForRetreivingRecordTrait, 
        BaseRepositoryForCreatingAndUpdatingRecordTrait, 
        BaseRepositoryForDeletingAndRestoringRecordTrait,
        BaseRepositoryForOtherOperationsRecordTrait;

    /**
     * Constructor to inject the model dependency.
     *
     * @param Model $model The Eloquent model instance.
     */
    public function __construct(protected Model $model) {}

    // public function getAll(array $columns = ['*'], bool $withTrashed = false, bool $onlyTrashed = false, array $conditions = []): Collection
    // {
    //     try {
    //         $query = $this->model->newQuery();

    //         if ($onlyTrashed) {
    //             $query = $this->model->onlyTrashed();
    //         } elseif ($withTrashed) {
    //             $query = $this->model->withTrashed();
    //         }

    //         if (!empty($conditions)) {
    //             $this->applyConditions($query, $conditions);
    //         }

    //         $columns = $this->mapRelationshipColumns($columns);

    //         return $query->get($columns);
    //     } catch (InvalidArgumentException $queryException) {
    //         throw new \RuntimeException("Invalid column for condition name", 500, $queryException);
    //     } catch (QueryException $queryException) {
    //         Log::error("Database error in " . get_class($this->model) . ": {$queryException->getMessage()}", [
    //             'model' => get_class($this->model),
    //             'sql' => $queryException->getSql(),
    //             'bindings' => $queryException->getBindings(),
    //             'trace' => $queryException->getTraceAsString(),
    //         ]);

    //         throw new \RuntimeException("Database error while retrieving records", 500, $queryException);
    //     } catch (\Exception $exception) {
    //         Log::error("Unexpected error in " . get_class($this->model) . ": {$exception->getMessage()}", [
    //             'model' => get_class($this->model),
    //             'trace' => $exception->getTraceAsString(),
    //         ]);
    //         throw new \RuntimeException("Unexpected error while retrieving records", 500, $exception);
    //     }
    // }

    // public function paginate(int $perPage = 15, array $columns = ['*'], string $pageName = 'page', int $page = 1, bool $withTrashed = false, bool $onlyTrashed = false, array $conditions = []): LengthAwarePaginator
    // {
    //     try {
    //         $query = $this->model->newQuery();

    //         if ($onlyTrashed) {
    //             $query->onlyTrashed();
    //         } elseif ($withTrashed) {
    //             $query->withTrashed();
    //         }

    //         if (!empty($conditions)) {
    //             $this->applyConditions($query, $conditions);
    //         }

    //         $columns = $this->mapRelationshipColumns($columns);

    //         $records = $query->paginate($perPage, $columns, $pageName, $page);
    //         return $records;
    //     } catch (QueryException $queryException) {
    //         Log::error("Database error during pagination in " . get_class($this->model) . ": {$queryException->getMessage()}", [
    //             'model' => get_class($this->model),
    //             'sql' => $queryException->getSql(),
    //             'bindings' => $queryException->getBindings(),
    //             'trace' => $queryException->getTraceAsString(),
    //         ]);

    //         throw new \RuntimeException("Database error while paginating records", 500, $queryException);
    //     } catch (InvalidArgumentException $queryException) {
    //         throw new \RuntimeException("Invalid column for condition name", 500, $queryException);
    //     } catch (\Exception $exception) {
    //         Log::error("Unexpected error during pagination in " . get_class($this->model) . ": {$exception->getMessage()}", [
    //             'model' => get_class($this->model),
    //             'trace' => $exception->getTraceAsString(),
    //         ]);

    //         throw new \RuntimeException("Unexpected error while paginating records", 500, $exception);
    //     }
    // }

    // public function findById(int|string $id, array $columns = ['*']): ?Model
    // {
    //     try {
    //         $columns = $this->mapRelationshipColumns($columns);

    //         return $this->model->findOrFail($id, $columns);
    //     } catch (ModelNotFoundException $e) {
    //         Log::warning("Record not found in " . get_class($this->model) . " with ID: {$id}", [
    //             'model' => get_class($this->model),
    //             'id' => $id,
    //         ]);
    //         throw new \RuntimeException("Record not found", 404, $e);
    //     } catch (QueryException $queryException) {
    //         Log::error("Database error in " . get_class($this->model) . ": {$queryException->getMessage()}", [
    //             'model' => get_class($this->model),
    //             'sql' => $queryException->getSql(),
    //             'bindings' => $queryException->getBindings(),
    //             'trace' => $queryException->getTraceAsString(),
    //         ]);

    //         throw new \RuntimeException("Database error while retrieving records", 500, $queryException);
    //     } catch (\Exception $exception) {
    //         Log::error("Unexpected error in " . get_class($this->model) . ": {$exception->getMessage()}", [
    //             'model' => get_class($this->model),
    //             'trace' => $exception->getTraceAsString(),
    //         ]);

    //         throw new \RuntimeException("Unexpected error while retrieving records", 500, $exception);
    //     }
    // }

    // public function findByField(string $field, mixed $value, array $columns = ['*']): ?Model
    // {
    //     try {
    //         $columns = $this->mapRelationshipColumns($columns);

    //         return $this->model->where($field, $value)->first($columns);
    //     } catch (ModelNotFoundException $e) {
    //         Log::warning("Record not found in " . get_class($this->model), [
    //             'model' => get_class($this->model),
    //         ]);
    //         throw new \RuntimeException("Record not found", 404, $e);
    //     } catch (QueryException $queryException) {
    //         Log::error("Database error in " . get_class($this->model) . ": {$queryException->getMessage()}", [
    //             'model' => get_class($this->model),
    //             'sql' => $queryException->getSql(),
    //             'bindings' => $queryException->getBindings(),
    //             'trace' => $queryException->getTraceAsString(),
    //         ]);

    //         throw new \RuntimeException("Database error while retrieving records", 500, $queryException);
    //     } catch (\Exception $exception) {
    //         Log::error("Unexpected error in " . get_class($this->model) . ": {$exception->getMessage()}", [
    //             'model' => get_class($this->model),
    //             'trace' => $exception->getTraceAsString(),
    //         ]);

    //         throw new \RuntimeException("Unexpected error while retrieving records", 500, $exception);
    //     }
    // }

    // public function create(array $data): Model
    // {
    //     try {
    //         return $this->model->create($data);
    //     } catch (QueryException $queryException) {
    //         Log::error("Database error in " . get_class($this->model) . ": {$queryException->getMessage()}", [
    //             'model' => get_class($this->model),
    //             'sql' => $queryException->getSql(),
    //             'bindings' => $queryException->getBindings(),
    //             'trace' => $queryException->getTraceAsString(),
    //         ]);

    //         throw new \RuntimeException("Database error while creating records", 500, $queryException);
    //     } catch (\Exception $exception) {
    //         Log::error("Unexpected error in " . get_class($this->model) . ": {$exception->getMessage()}", [
    //             'model' => get_class($this->model),
    //             'trace' => $exception->getTraceAsString(),
    //         ]);

    //         throw new \RuntimeException("Unexpected error while creating records", 500, $exception);
    //     }
    // }

    // public function update(int|string $id, array $data, array $columns = ['*']): Model
    // {
    //     try {

    //         $query = $this->model->newQuery()->withTrashed();

    //         $columns = $this->mapRelationshipColumns($columns);

    //         $record = $query->findOrFail($id, $columns);

    //         $record->update($data); // Update the record with the new data
    //         return $record->refresh(); // Refresh the model to reflect the latest changes
    //     } catch (ModelNotFoundException $e) {
    //         Log::warning("Record not found in " . get_class($this->model) . " with ID: {$id}", [
    //             'model' => get_class($this->model),
    //             'id' => $id,
    //         ]);
    //         throw new \RuntimeException("Record not found", 404, $e);
    //     } catch (QueryException $queryException) {
    //         // Handle database-related errors (e.g., connection issues, SQL syntax errors).
    //         // Log the error with detailed context for debugging purposes.
    //         Log::error("Database error in " . get_class($this->model) . ": {$queryException->getMessage()}", [
    //             'model' => get_class($this->model), // The model class where the error occurred.
    //             'sql' => $queryException->getSql(), // The SQL query that caused the error.
    //             'bindings' => $queryException->getBindings(), // The bindings used in the query.
    //             'trace' => $queryException->getTraceAsString(), // The stack trace for debugging.
    //         ]);

    //         // Rethrow the exception as a `RuntimeException` with a user-friendly message.
    //         // This ensures the calling code can handle the error appropriately.
    //         throw new \RuntimeException("Database error while updating records", 500, $queryException);
    //     } catch (\Exception $exception) {
    //         // Handle any unexpected errors that are not related to the database.
    //         // Log the error with detailed context for debugging purposes.
    //         Log::error("Unexpected error in " . get_class($this->model) . ": {$exception->getMessage()}", [
    //             'model' => get_class($this->model), // The model class where the error occurred.
    //             'trace' => $exception->getTraceAsString(), // The stack trace for debugging.
    //         ]);

    //         // Rethrow the exception as a `RuntimeException` with a user-friendly message.
    //         // This ensures the calling code can handle the error appropriately.
    //         throw new \RuntimeException("Unexpected error while updating records", 500, $exception);
    //     }
    // }

    // public function updateGroup(array $data, array $conditions = [], array $columns = ['*']): Collection
    // {
    //     try {

    //         $query = $this->model->newQuery();

    //         // Apply conditions if provided
    //         if (!empty($conditions)) {
    //             $this->applyConditions($query, $conditions);
    //         }

    //         // Fetch the records before updating (optional, for logging or comparison)
    //         $recordsBeforeUpdate = clone $query; // Clone before modifying
    //         $beforeUpdateData = $recordsBeforeUpdate->get(['id']);

    //         // Perform a bulk update
    //         $affectedRows = $query->update($data);

    //         $columns = $this->mapRelationshipColumns($columns);

    //         // Fetch updated records explicitly to avoid stale data issues
    //         $updatedRecords = $this->model->newQuery()->whereIn('id', $beforeUpdateData->pluck('id'))->get($columns);

    //         return $updatedRecords;

    //         // $query = $this->model->newQuery();
    //         // // Apply conditions if provided
    //         // if (!empty($conditions)) {
    //         //     $this->applyConditions($query, $conditions);
    //         // }
    //         // // Perform a bulk update
    //         // $affectedRows = $query->update($data);
    //         // // Fetch updated records if needed
    //         // $records = $query->get($columns);
    //         // return $records;
    //     } catch (ModelNotFoundException $e) {
    //         Log::warning("Records not found in " . get_class($this->model), [
    //             'model' => get_class($this->model),
    //         ]);
    //         throw new \RuntimeException("Record not found", 404, $e);
    //     } catch (InvalidArgumentException $queryException) {
    //         // Rethrow the exception as a `RuntimeException` with a user-friendly message.
    //         // This ensures the calling code can handle the error appropriately.
    //         throw new \RuntimeException("Invalid column for condition name", 500, $queryException);
    //     } catch (QueryException $queryException) {
    //         // Handle database-related errors (e.g., connection issues, SQL syntax errors).
    //         // Log the error with detailed context for debugging purposes.
    //         Log::error("Database error in " . get_class($this->model) . ": {$queryException->getMessage()}", [
    //             'model' => get_class($this->model), // The model class where the error occurred.
    //             'sql' => $queryException->getSql(), // The SQL query that caused the error.
    //             'bindings' => $queryException->getBindings(), // The bindings used in the query.
    //             'trace' => $queryException->getTraceAsString(), // The stack trace for debugging.
    //         ]);

    //         // Rethrow the exception as a `RuntimeException` with a user-friendly message.
    //         // This ensures the calling code can handle the error appropriately.
    //         throw new \RuntimeException("Database error while updating records", 500, $queryException);
    //     } catch (\Exception $exception) {
    //         // Handle any unexpected errors that are not related to the database.
    //         // Log the error with detailed context for debugging purposes.
    //         Log::error("Unexpected error in " . get_class($this->model) . ": {$exception->getMessage()}", [
    //             'model' => get_class($this->model), // The model class where the error occurred.
    //             'trace' => $exception->getTraceAsString(), // The stack trace for debugging.
    //         ]);

    //         // Rethrow the exception as a `RuntimeException` with a user-friendly message.
    //         // This ensures the calling code can handle the error appropriately.
    //         throw new \RuntimeException("Unexpected error while updating records", 500, $exception);
    //     }
    // }

    // public function delete(int|string $id, bool $force = false): bool
    // {
    //     try {
    //         $record = $this->model->newQuery()->withTrashed()->find($id);

    //         if (!$record) {
    //             throw new ModelNotFoundException("Record with ID {$id} not found.");
    //         }

    //         return $force ? (bool) $record->forceDelete() : (bool) $record->delete();
    //     } catch (ModelNotFoundException $e) {
    //         Log::warning("Record not found in " . get_class($this->model) . " with ID: {$id}", [
    //             'model' => get_class($this->model),
    //             'id' => $id,
    //         ]);
    //         throw new \RuntimeException("Record not found", 404, $e);
    //     } catch (QueryException $queryException) {
    //         // Handle database-related errors (e.g., connection issues, SQL syntax errors).
    //         // Log the error with detailed context for debugging purposes.
    //         Log::error("Database error in " . get_class($this->model) . ": {$queryException->getMessage()}", [
    //             'model' => get_class($this->model), // The model class where the error occurred.
    //             'sql' => $queryException->getSql(), // The SQL query that caused the error.
    //             'bindings' => $queryException->getBindings(), // The bindings used in the query.
    //             'trace' => $queryException->getTraceAsString(), // The stack trace for debugging.
    //         ]);

    //         // Rethrow the exception as a `RuntimeException` with a user-friendly message.
    //         // This ensures the calling code can handle the error appropriately.
    //         throw new \RuntimeException("Database error while deleting records", 500, $queryException);
    //     } catch (\Exception $exception) {
    //         // Handle any unexpected errors that are not related to the database.
    //         // Log the error with detailed context for debugging purposes.
    //         Log::error("Unexpected error in " . get_class($this->model) . ": {$exception->getMessage()}", [
    //             'model' => get_class($this->model), // The model class where the error occurred.
    //             'trace' => $exception->getTraceAsString(), // The stack trace for debugging.
    //         ]);

    //         // Rethrow the exception as a `RuntimeException` with a user-friendly message.
    //         // This ensures the calling code can handle the error appropriately.
    //         throw new \RuntimeException("Unexpected error while deleting records", 500, $exception);
    //     }
    // }

    // public function deleteBulkRecords(array $conditions, bool $force = false): int
    // {
    //     try {
    //         $query = $this->model->newQuery()->withTrashed();

    //         // Apply conditions if provided
    //         $this->applyConditions($query, $conditions);

    //         // Perform deletion and ensure it always returns an integer
    //         return (int) ($force ? $query->forceDelete() : $query->delete());
    //     } catch (ModelNotFoundException $e) {
    //         Log::warning("Record not found in " . get_class($this->model), [
    //             'model' => get_class($this->model),
    //         ]);
    //         throw new \RuntimeException("Record not found", 404, $e);
    //     } catch (InvalidArgumentException $queryException) {
    //         // Rethrow the exception as a `RuntimeException` with a user-friendly message.
    //         // This ensures the calling code can handle the error appropriately.
    //         throw new \RuntimeException("Invalid column for condition name", 500, $queryException);
    //     } catch (QueryException $queryException) {
    //         // Handle database-related errors (e.g., connection issues, SQL syntax errors).
    //         // Log the error with detailed context for debugging purposes.
    //         Log::error("Database error in " . get_class($this->model) . ": {$queryException->getMessage()}", [
    //             'model' => get_class($this->model), // The model class where the error occurred.
    //             'sql' => $queryException->getSql(), // The SQL query that caused the error.
    //             'bindings' => $queryException->getBindings(), // The bindings used in the query.
    //             'trace' => $queryException->getTraceAsString(), // The stack trace for debugging.
    //         ]);

    //         // Rethrow the exception as a `RuntimeException` with a user-friendly message.
    //         // This ensures the calling code can handle the error appropriately.
    //         throw new \RuntimeException("Database error while deleting records", 500, $queryException);
    //     } catch (\Exception $exception) {
    //         // Handle any unexpected errors that are not related to the database.
    //         // Log the error with detailed context for debugging purposes.
    //         Log::error("Unexpected error in " . get_class($this->model) . ": {$exception->getMessage()}", [
    //             'model' => get_class($this->model), // The model class where the error occurred.
    //             'trace' => $exception->getTraceAsString(), // The stack trace for debugging.
    //         ]);

    //         // Rethrow the exception as a `RuntimeException` with a user-friendly message.
    //         // This ensures the calling code can handle the error appropriately.
    //         throw new \RuntimeException("Unexpected error while deleting records", 500, $exception);
    //     }
    // }

    // public function checkIsSoftdDeleted(int|string $id): bool
    // {
    //     try {
    //         $record = $this->model->newQuery()->onlyTrashed()->find($id);

    //         return $record ? true : false;
    //     } catch (QueryException $queryException) {
    //         // Handle database-related errors (e.g., connection issues, SQL syntax errors).
    //         // Log the error with detailed context for debugging purposes.
    //         Log::error("Database error in " . get_class($this->model) . ": {$queryException->getMessage()}", [
    //             'model' => get_class($this->model), // The model class where the error occurred.
    //             'sql' => $queryException->getSql(), // The SQL query that caused the error.
    //             'bindings' => $queryException->getBindings(), // The bindings used in the query.
    //             'trace' => $queryException->getTraceAsString(), // The stack trace for debugging.
    //         ]);

    //         // Rethrow the exception as a `RuntimeException` with a user-friendly message.
    //         // This ensures the calling code can handle the error appropriately.
    //         throw new \RuntimeException("Database error while checking records", 500, $queryException);
    //     } catch (\Exception $exception) {
    //         // Handle any unexpected errors that are not related to the database.
    //         // Log the error with detailed context for debugging purposes.
    //         Log::error("Unexpected error in " . get_class($this->model) . ": {$exception->getMessage()}", [
    //             'model' => get_class($this->model), // The model class where the error occurred.
    //             'trace' => $exception->getTraceAsString(), // The stack trace for debugging.
    //         ]);

    //         // Rethrow the exception as a `RuntimeException` with a user-friendly message.
    //         // This ensures the calling code can handle the error appropriately.
    //         throw new \RuntimeException("Unexpected error while checking records", 500, $exception);
    //     }
    // }

    // public function restoreSoftdDeleted(int|string $id, array $columns = ['*']): Model
    // {
    //     try {
    //         $columns = $this->mapRelationshipColumns($columns);

    //         $recordInTrashed = $this->model->newQuery()->onlyTrashed()->find($id, $columns);

    //         if (!$recordInTrashed) {
    //             $recordInNonTrashed = $this->model->newQuery()->find($id, $columns);
    //             if (!$recordInNonTrashed) {
    //                 throw new ModelNotFoundException("Record with ID {$id} not found in trashed or non-trashed.");
    //             }
    //             return $recordInNonTrashed; // No need for `first($columns)`
    //         }

    //         // Restore the record first
    //         $recordInTrashed->restore();

    //         // Refresh the instance to get the latest data after restore
    //         $recordInTrashed->refresh();

    //         return $recordInTrashed;
    //     } catch (ModelNotFoundException $e) {
    //         Log::warning("Record not found in " . get_class($this->model) . " with ID: {$id}", [
    //             'model' => get_class($this->model),
    //             'id' => $id,
    //         ]);
    //         throw new \RuntimeException("Record with ID {$id} not found in trashed or non trashed.", 404, $e);
    //     } catch (QueryException $queryException) {
    //         // Handle database-related errors (e.g., connection issues, SQL syntax errors).
    //         // Log the error with detailed context for debugging purposes.
    //         Log::error("Database error in " . get_class($this->model) . ": {$queryException->getMessage()}", [
    //             'model' => get_class($this->model), // The model class where the error occurred.
    //             'sql' => $queryException->getSql(), // The SQL query that caused the error.
    //             'bindings' => $queryException->getBindings(), // The bindings used in the query.
    //             'trace' => $queryException->getTraceAsString(), // The stack trace for debugging.
    //         ]);

    //         // Rethrow the exception as a `RuntimeException` with a user-friendly message.
    //         // This ensures the calling code can handle the error appropriately.
    //         throw new \RuntimeException("Database error while restoring records", 500, $queryException);
    //     } catch (\Exception $exception) {
    //         // Handle any unexpected errors that are not related to the database.
    //         // Log the error with detailed context for debugging purposes.
    //         Log::error("Unexpected error in " . get_class($this->model) . ": {$exception->getMessage()}", [
    //             'model' => get_class($this->model), // The model class where the error occurred.
    //             'trace' => $exception->getTraceAsString(), // The stack trace for debugging.
    //         ]);

    //         // Rethrow the exception as a `RuntimeException` with a user-friendly message.
    //         // This ensures the calling code can handle the error appropriately.
    //         throw new \RuntimeException("Unexpected error while restoring records", 500, $exception);
    //     }
    // }

    // public function restoreBulkRecords(array $conditions = [], array $columns = ['*']): Collection
    // {
    //     try {

    //         $query = $this->model->newQuery()->onlyTrashed();

    //         // Apply conditions if provided
    //         if (!empty($conditions)) {
    //             $this->applyConditions($query, $conditions);
    //         }

    //         $columns = $this->mapRelationshipColumns($columns);

    //         // Fetch the records before restoring
    //         $trashedRecords = $query->get($columns);

    //         // Restore the soft-deleted records
    //         $query->restore();

    //         return $trashedRecords; // Return the restored records
    //     } catch (ModelNotFoundException $e) {
    //         Log::warning("Record not found in " . get_class($this->model), [
    //             'model' => get_class($this->model),
    //         ]);
    //         throw new \RuntimeException("Record not found", 404, $e);
    //     } catch (QueryException $queryException) {
    //         // Handle database-related errors (e.g., connection issues, SQL syntax errors).
    //         // Log the error with detailed context for debugging purposes.
    //         Log::error("Database error in " . get_class($this->model) . ": {$queryException->getMessage()}", [
    //             'model' => get_class($this->model), // The model class where the error occurred.
    //             'sql' => $queryException->getSql(), // The SQL query that caused the error.
    //             'bindings' => $queryException->getBindings(), // The bindings used in the query.
    //             'trace' => $queryException->getTraceAsString(), // The stack trace for debugging.
    //         ]);

    //         // Rethrow the exception as a `RuntimeException` with a user-friendly message.
    //         // This ensures the calling code can handle the error appropriately.
    //         throw new \RuntimeException("Database error while deleting records", 500, $queryException);
    //     } catch (\Exception $exception) {
    //         // Handle any unexpected errors that are not related to the database.
    //         // Log the error with detailed context for debugging purposes.
    //         Log::error("Unexpected error in " . get_class($this->model) . ": {$exception->getMessage()}", [
    //             'model' => get_class($this->model), // The model class where the error occurred.
    //             'trace' => $exception->getTraceAsString(), // The stack trace for debugging.
    //         ]);

    //         // Rethrow the exception as a `RuntimeException` with a user-friendly message.
    //         // This ensures the calling code can handle the error appropriately.
    //         throw new \RuntimeException("Unexpected error while deleting records", 500, $exception);
    //     }
    // }


    // public function count(array $conditions = []): int
    // {
    //     return 1;
    // }

















}