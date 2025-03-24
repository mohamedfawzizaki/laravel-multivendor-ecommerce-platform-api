<?php

namespace App\Repositories\EloquentBased;

use Exception;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait BaseRepositoryForCreatingAndUpdatingRecordTrait
{
    public function create(array $data): Model
    {
        try {
            return $this->model->create($data);
        } catch (QueryException $queryException) {
            Log::error("Database error in " . get_class($this->model) . ": {$queryException->getMessage()}", [
                'model' => get_class($this->model),
                'sql' => $queryException->getSql(),
                'bindings' => $queryException->getBindings(),
                'trace' => $queryException->getTraceAsString(),
            ]);

            throw new \RuntimeException("Database error while creating records", 500, $queryException);
        } catch (Exception $exception) {
            Log::error("Unexpected error in " . get_class($this->model) . ": {$exception->getMessage()}", [
                'model' => get_class($this->model),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw new \RuntimeException("Unexpected error while creating records", 500, $exception);
        }
    }

    public function update(int|string $id, array $data, array $columns = ['*']): Model
    {
        try {
            
            $query = $this->model->newQuery();

            $columns = $this->mapRelationshipColumns($columns);

            $record = $query->findOrFail($id, $columns);

            $record->update($data); // Update the record with the new data
            $record->refresh(); // Refresh the model to reflect the latest changes
            return $record;
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
        } catch (Exception $exception) {
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

    public function updateGroup(array $data, array $conditions = [], array $columns = ['*']): Collection
    {
        try {

            $query = $this->model->newQuery();

            // Apply conditions if provided
            if (!empty($conditions)) {
                $this->applyConditions($query, $conditions);
            }

            // Fetch the records before updating (optional, for logging or comparison)
            $recordsBeforeUpdate = clone $query; // Clone before modifying
            $beforeUpdateData = $recordsBeforeUpdate->get(['id']);

            // Perform a bulk update
            $affectedRows = $query->update($data);

            $columns = $this->mapRelationshipColumns($columns);

            // Fetch updated records explicitly to avoid stale data issues
            $updatedRecords = $this->model->newQuery()->whereIn('id', $beforeUpdateData->pluck('id'))->get($columns);

            return $updatedRecords;

            // $query = $this->model->newQuery();
            // // Apply conditions if provided
            // if (!empty($conditions)) {
            //     $this->applyConditions($query, $conditions);
            // }
            // // Perform a bulk update
            // $affectedRows = $query->update($data);
            // // Fetch updated records if needed
            // $records = $query->get($columns);
            // return $records;
        } catch (ModelNotFoundException $e) {
            Log::warning("Records not found in " . get_class($this->model), [
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
            throw new \RuntimeException("Database error while updating records", 500, $queryException);
        } catch (Exception $exception) {
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
}