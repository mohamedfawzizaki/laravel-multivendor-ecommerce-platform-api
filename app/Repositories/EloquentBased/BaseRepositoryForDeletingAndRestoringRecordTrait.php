<?php

namespace App\Repositories\EloquentBased;

use Exception;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait BaseRepositoryForDeletingAndRestoringRecordTrait
{
    public function delete(int|string $id, bool $force = false): bool
    {
        try {
            $query = $this->model->newQuery();

            try { // if the model doesnot implement softdeletetrait
                if ($force) {
                    $query->withTrashed();
                }
            } catch (Exception $exception) {
                Log::error($exception->getMessage());
            }

            $record = $query->find($id);

            if (!$record) {
                throw new ModelNotFoundException("Record with ID {$id} not found.");
            }

            try {
                if (!$force) {
                    $record->delete();
                    return false;
                }
            } catch (Exception $exception) {
                Log::error($exception->getMessage());
                return (bool) $record->forceDelete();
            }

            return (bool) $record->forceDelete();
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
        } catch (Exception $exception) {
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
            $query = $this->model->newQuery();

            try { // if the model doesnot implement softdeletetrait
                if ($force) {
                    $query = $query->withTrashed();
                }
            } catch (Exception $exception) {
                Log::error($exception->getMessage());
            }

            // Apply conditions if provided
            $this->applyConditions($query, $conditions);
            
            try {
                if (!$force) {
                    return (int) $query->delete();
                }
            } catch (Exception $exception) {
                Log::error($exception->getMessage());
                return (int) $query->forceDelete();
            }

            return (int) $query->forceDelete();
        } catch (ModelNotFoundException $e) {
            Log::warning("Record not found in " . get_class($this->model), [
                'model' => get_class($this->model),
            ]);
            throw new \RuntimeException("Record not found", 404, $e);
        } catch (InvalidArgumentException $queryException) {
            // Rethrow the exception as a `RuntimeException` with a user-friendly message.
            // This ensures the calling code can handle the error appropriately.
            throw new \RuntimeException("Invalid column for condition name", 500, $queryException);
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
        } catch (Exception $exception) {
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

    public function checkIsSoftdDeleted(int|string $id): bool
    {
        try {
            $query = $this->model->newQuery();

            try {
                $query->onlyTrashed();
            } catch (Exception $exception) {
                Log::error("Model does not implement the soft deletion trait : " . get_class($this->model) . ": {$exception->getMessage()}", [
                    'model' => get_class($this->model), // The model class where the error occurred.
                    'trace' => $exception->getTraceAsString(), // The stack trace for debugging.
                ]);
                throw new \RuntimeException("", 500, $exception);
                // return false;
            }

            $record = $query->find($id);

            return $record ? true : false;
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
            throw new \RuntimeException("Database error while checking records", 500, $queryException);
        } catch (Exception $exception) {
            // Handle any unexpected errors that are not related to the database.
            // Log the error with detailed context for debugging purposes.
            Log::error("Unexpected error in " . get_class($this->model) . ": {$exception->getMessage()}", [
                'model' => get_class($this->model), // The model class where the error occurred.
                'trace' => $exception->getTraceAsString(), // The stack trace for debugging.
            ]);

            // Rethrow the exception as a `RuntimeException` with a user-friendly message.
            // This ensures the calling code can handle the error appropriately.
            throw new \RuntimeException("Unexpected error while checking records", 500, $exception);
        }
    }

    public function restoreSoftdDeleted(int|string $id, array $columns = ['*']): Model
    {
        try {
            $columns = $this->mapRelationshipColumns($columns);

            $query = $this->model->newQuery();

            try {
                $query->onlyTrashed();
            } catch (Exception $exception) {
                Log::error("Model does not implement the soft deletion trait : " . get_class($this->model) . ": {$exception->getMessage()}", [
                    'model' => get_class($this->model),  
                    'trace' => $exception->getTraceAsString(),  
                ]);
                throw new \RuntimeException("", 500, $exception);
            }
            
            $recordInTrashed = $query->find($id, $columns);

            if (!$recordInTrashed) {
                $recordInNonTrashed = $this->model->newQuery()->find($id, $columns);
                if (!$recordInNonTrashed) {
                    throw new ModelNotFoundException("Record with ID {$id} not found in trashed or non-trashed.");
                }
                return $recordInNonTrashed; // No need for `first($columns)`
            }

            // Restore the record first
            $recordInTrashed->restore();

            // Refresh the instance to get the latest data after restore
            $recordInTrashed->refresh();

            return $recordInTrashed;
        } catch (ModelNotFoundException $e) {
            Log::warning("Record not found in " . get_class($this->model) . " with ID: {$id}", [
                'model' => get_class($this->model),
                'id' => $id,
            ]);
            throw new \RuntimeException("Record with ID {$id} not found in trashed or non trashed.", 404, $e);
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
            throw new \RuntimeException("Database error while restoring records", 500, $queryException);
        } catch (Exception $exception) {
            // Handle any unexpected errors that are not related to the database.
            // Log the error with detailed context for debugging purposes.
            Log::error("Unexpected error in " . get_class($this->model) . ": {$exception->getMessage()}", [
                'model' => get_class($this->model), // The model class where the error occurred.
                'trace' => $exception->getTraceAsString(), // The stack trace for debugging.
            ]);

            // Rethrow the exception as a `RuntimeException` with a user-friendly message.
            // This ensures the calling code can handle the error appropriately.
            throw new \RuntimeException("Unexpected error while restoring records", 500, $exception);
        }
    }

    public function restoreBulkRecords(array $conditions = [], array $columns = ['*']): Collection
    {
        try {

            $query = $this->model->newQuery()->onlyTrashed();

            // Apply conditions if provided
            if (!empty($conditions)) {
                $this->applyConditions($query, $conditions);
            }

            $columns = $this->mapRelationshipColumns($columns);

            // Fetch the records before restoring
            $trashedRecords = $query->get($columns);

            // Restore the soft-deleted records
            $query->restore();

            return $trashedRecords; // Return the restored records
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
        } catch (Exception $exception) {
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
}