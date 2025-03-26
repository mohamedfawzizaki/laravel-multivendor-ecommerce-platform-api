<?php

namespace App\Repositories;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

trait RepositoryBaseTrait
{
    public function getAllUsingRepositoryBaseTrait(
        ?int $perPage = null,
        array $columns = ['*'],
        ?string $pageName = null,
        ?int $page = null,
        bool $withTrashed = false,
        bool $onlyTrashed = false,
        array $conditions = []
    ): Collection|LengthAwarePaginator {
        $validColumns = $columns !== ['*'] ? $this->getValidColumns($columns) : ['*'];

        $records = ($perPage && $page)
            ? $this->paginate($perPage, $validColumns, $pageName, $page, $withTrashed, $onlyTrashed, $conditions)
            : $this->getAll($validColumns, $withTrashed, $onlyTrashed, $conditions);

        return $this->prepareRetreivedCollection($records, $validColumns);
        // // If no specific columns are requested, ensure relationships are loaded.
        // if ($validColumns == ['*']) {
        //     // Get the relationships to load from the repository.
        //     $relationships = $this->getRelationships();
        //     // Load missing relationships for each record if they are defined.
        //     if (!empty($relationships)) {
        //         foreach ($records as $record) {
        //             $record->loadMissing($relationships);
        //         }
        //     }
        // }
        // // Hide pivot attributes from the retrieved records' relationships.
        // $records = $this->hidePivot($records, function ($record) {
        //     $this->hideModelPivot($record, $this->getRelationships());
        // });
        // // If specific columns are requested, return only those columns.
        // // This avoids including relationship data in the response.
        // // Otherwise, return the full record object.
        // if (!$records->isEmpty()) {
        //     return $validColumns !== ['*'] ? $this->getSpecificColumnsFromCollection($records, $validColumns) : $records;
        // }
        // // return empty collection:
        // return $records;
    }

    public function getByIdUsingRepositoryBaseTrait(int|string $id, array $columns = ['*']): Model
    {
        $validColumns = $columns !== ['*'] ? $this->getValidColumns($columns) : ['*'];

        $record = $this->findById($id, $validColumns);

        return $this->prepareRetreivedModel($record, $validColumns);
        // /// If no specific columns are requested, ensure relationships are loaded.
        // if ($validColumns == ['*']) {
        //     // Get the relationships to load from the repository.
        //     $relationships = $this->getRelationships();

        //     // Load missing relationships if they are defined.
        //     if (!empty($relationships)) {
        //         $record->loadMissing($relationships);
        //     }
        // }

        // // Hide pivot attributes from the retrieved record's relationships.
        // $this->hideModelPivot($record, $this->getRelationships());

        // // If specific columns are requested, return only those columns.
        // // to avoid appearance of the relationships data, else // Return the full record object.
        // return $validColumns !== ['*'] ? $this->getSpecificColumnsFromSingleModel($record, $validColumns) : $record;
    }

    public function searchByUsingRepositoryBaseTrait(string $field, mixed $value, array $columns = ['*']): ?object
    {
        $validColumns = $columns !== ['*'] ? $this->getValidColumns($columns) : ['*'];
        $validField  = $this->getValidColumns([$field])[0];

        $record = $this->findByField($validField, $value, $validColumns);

        return $this->prepareRetreivedModel($record, $validColumns);
    }

    public function createUsingRepositoryBaseTrait(array $data): ?object
    {
        $data = $this->prepareDataForMassAssignment($this->getFillable(), $data);
        $record = $this->create($data);

        // Get the relationships to load from the repository.
        $pivotWith = $this->getPivotWith();

        // Assign default entries to pivot table if applicable
        if ($this->hasPivot()) {
            foreach ($pivotWith as $relation) {
                if (method_exists($record, $relation) && method_exists($record->$relation(), 'attach')) {
                    // Attach default pivot data (Customize this as needed)
                    $record->$relation()->syncWithoutDetaching($this->getDefualtIDsForPivot()[$relation]);
                }
            }
        }

        $relationships = $this->getRelationships();
        // Load missing relationships if they are defined.
        if (!empty($relationships)) {
            $record->load($relationships);
        }
        // Hide pivot attributes from the retrieved record's relationships.
        $this->hideModelPivot($record, $this->getRelationships());

        return $record;
    }

    public function updateUsingRepositoryBaseTrait(int|string $id, array $data, array $columns = ['*']): ?object
    {
        $validColumns = $columns !== ['*'] ? $this->getValidColumns($columns) : ['*'];

        $data = $this->prepareDataForMassAssignment($this->getFillable(), $data);

        $record = $this->update($id, $data);


        return $this->prepareRetreivedModel($record, $validColumns);
    }

    public function updateGroupUsingRepositoryBaseTrait(array $data, array $conditions = [], array $columns = ['*']): Collection
    {
        $validColumns = $columns !== ['*'] ? $this->getValidColumns($columns) : ['*'];

        $data = $this->prepareDataForMassAssignment($this->getAvailableColumnsForMassUpdate(), $data);

        $records = $this->updateGroup($data, $conditions, $validColumns);

        return $this->prepareRetreivedCollection($records, $validColumns);
    }

    public function deleteUsingRepositoryBaseTrait(int|string $id, bool $force = false)
    {
        return $this->delete($id, $force);
    }

    public function deleteBulkUsingRepositoryBaseTrait(array $conditions, bool $force = false)
    {
        return $this->deleteBulkRecords($conditions, $force);
    }

    public function softDeletedUsingRepositoryBaseTrait(int|string $id)
    {
        return $this->checkIsSoftdDeleted($id);
    }

    public function restoreUsingRepositoryBaseTrait(int|string $id, array $columns = ['*'])
    {
        $validColumns = $columns !== ['*'] ? $this->getValidColumns($columns) : ['*'];

        $record = $this->restoreSoftdDeleted($id, $validColumns);

        return $this->prepareRetreivedModel($record, $validColumns);
    }

    public function restoreBulkUsingRepositoryBaseTrait(array $conditions = [], array $columns = ['*'])
    {
        $validColumns = $columns !== ['*'] ? $this->getValidColumns($columns) : ['*'];

        $records = $this->restoreBulkRecords($conditions, $validColumns);

        return $this->prepareRetreivedCollection($records, $validColumns);
    }

}