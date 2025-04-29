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

trait BaseRepositoryForRetreivingRecordTrait
{
    public function getAll(array $columns = ['*'], bool $withTrashed = false, bool $onlyTrashed = false, array $conditions = []): Collection
    {   
        try {
            $query = $this->model->newQuery();
            
            
            try { // if the model doesnot implement softdeletetrait
                if ($onlyTrashed) {
                    $query = $this->model->onlyTrashed();
                } elseif ($withTrashed) {
                    $query = $this->model->withTrashed();
                }
            } catch (Exception $exception) {
                Log::error($exception->getMessage());
            }
            
            if (!empty($conditions)) {
                $this->applyConditions($query, $conditions);
            }
            
            $columns = $this->mapRelationshipColumns($columns);

            return $query->get($columns);
        } catch (InvalidArgumentException $queryException) {
            throw new \RuntimeException("Invalid column for condition name", 500, $queryException);
        } catch (QueryException $queryException) {
            Log::error("Database error in " . get_class($this->model) . ": {$queryException->getMessage()}", [
                'model' => get_class($this->model),
                'sql' => $queryException->getSql(),
                'bindings' => $queryException->getBindings(),
                'trace' => $queryException->getTraceAsString(),
            ]);

            throw new \RuntimeException("Database error while retrieving records", 500, $queryException);
        } catch (Exception $exception) {
            Log::error("Unexpected error in " . get_class($this->model) . ": {$exception->getMessage()}", [
                'model' => get_class($this->model),
                'trace' => $exception->getTraceAsString(),
            ]);
            throw new \RuntimeException("Unexpected error while retrieving records", 500, $exception);
        }
    }

    public function paginate(int $perPage = 15, array $columns = ['*'], string $pageName = 'page', int $page = 1, bool $withTrashed = false, bool $onlyTrashed = false, array $conditions = []): LengthAwarePaginator
    {
        try {
            $query = $this->model->newQuery();

            try { // if the model doesnot implement softdeletetrait
                if ($onlyTrashed) {
                    $query = $this->model->onlyTrashed();
                } elseif ($withTrashed) {
                    $query = $this->model->withTrashed();
                }
            } catch (Exception $exception) {
                Log::error($exception->getMessage());
            }

            if (!empty($conditions)) {
                $this->applyConditions($query, $conditions);
            }

            $columns = $this->mapRelationshipColumns($columns);

            $records = $query->paginate($perPage, $columns, $pageName, $page);
            return $records;
        } catch (QueryException $queryException) {
            Log::error("Database error during pagination in " . get_class($this->model) . ": {$queryException->getMessage()}", [
                'model' => get_class($this->model),
                'sql' => $queryException->getSql(),
                'bindings' => $queryException->getBindings(),
                'trace' => $queryException->getTraceAsString(),
            ]);

            throw new \RuntimeException("Database error while paginating records", 500, $queryException);
        } catch (InvalidArgumentException $queryException) {
            throw new \RuntimeException("Invalid column for condition name", 500, $queryException);
        } catch (Exception $exception) {
            Log::error("Unexpected error during pagination in " . get_class($this->model) . ": {$exception->getMessage()}", [
                'model' => get_class($this->model),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw new \RuntimeException("Unexpected error while paginating records", 500, $exception);
        }
    }

    public function findById(int|string $id, array $columns = ['*']): ?Model
    {
        try {
            $columns = $this->mapRelationshipColumns($columns);

            return $this->model->find($id, $columns);
        } catch (ModelNotFoundException $e) {
            Log::warning("Record not found in " . get_class($this->model) . " with ID: {$id}", [
                'model' => get_class($this->model),
                'id' => $id,
            ]);
            throw new \RuntimeException("Record not found", 404, $e);
        } catch (QueryException $queryException) {
            Log::error("Database error in " . get_class($this->model) . ": {$queryException->getMessage()}", [
                'model' => get_class($this->model),
                'sql' => $queryException->getSql(),
                'bindings' => $queryException->getBindings(),
                'trace' => $queryException->getTraceAsString(),
            ]);

            throw new \RuntimeException("Database error while retrieving records", 500, $queryException);
        } catch (Exception $exception) {
            Log::error("Unexpected error in " . get_class($this->model) . ": {$exception->getMessage()}", [
                'model' => get_class($this->model),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw new \RuntimeException("Unexpected error while retrieving records", 500, $exception);
        }
    }

    public function findByField(string $field, mixed $value, array $columns = ['*']): ?Model
    {
        try {
            $columns = $this->mapRelationshipColumns($columns);

            return $this->model->where($field, 'like', "%$value%")->first($columns);
        } catch (ModelNotFoundException $e) {
            Log::warning("Record not found in " . get_class($this->model), [
                'model' => get_class($this->model),
            ]);
            throw new \RuntimeException("Record not found", 404, $e);
        } catch (QueryException $queryException) {
            Log::error("Database error in " . get_class($this->model) . ": {$queryException->getMessage()}", [
                'model' => get_class($this->model),
                'sql' => $queryException->getSql(),
                'bindings' => $queryException->getBindings(),
                'trace' => $queryException->getTraceAsString(),
            ]);

            throw new \RuntimeException("Database error while retrieving records", 500, $queryException);
        } catch (Exception $exception) {
            Log::error("Unexpected error in " . get_class($this->model) . ": {$exception->getMessage()}", [
                'model' => get_class($this->model),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw new \RuntimeException("Unexpected error while retrieving records", 500, $exception);
        }
    }
}