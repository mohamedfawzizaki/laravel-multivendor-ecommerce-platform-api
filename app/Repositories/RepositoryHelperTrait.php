<?php

namespace App\Repositories;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

trait RepositoryHelperTrait
{
    public function getValidColumns(array $columns): array
    {
        // Get available standard columns
        $availableColumns = $this->getAvailableColumns();

        // Merge available columns with relationship aliases
        $allAvailableColumns = array_merge($availableColumns, array_keys($this->getRelationshipMap()));

        $filteredColumns = array_filter($columns, function ($column) use ($allAvailableColumns) {
            return in_array($column, $allAvailableColumns);
        });

        // If no valid columns are found, log an error and throw an exception.
        if (empty($filteredColumns)) {
            Log::error('The specified columns in the query string are not valid.');
            throw new Exception(
                'The specified columns in the query string are not valid. Available columns are: ' .
                    implode(', ', $this->getAvailableColumns())
            );
        }

        return $filteredColumns;
    }

    public function mapRelationshipColumns($columns): array
    {
        // Fetch the relationship map once
        $relationshipMap = $this->getRelationshipMap();
        // Replace relationship aliases in the requested columns
        $columns = array_map(fn($column) => $relationshipMap[$column] ?? $column, $columns);

        return $columns;
    }

    public function getSpecificColumnsFromSingleModel($model, $columns)
    {
        foreach ($columns as $column) {
            $modelData[$column] = $model->$column;
        }
        return (object) $modelData;
    }

    public function getSpecificColumnsFromCollection($collection, $columns)
    {
        foreach ($collection as $entity) {
            $entityData = [];
            foreach ($columns as $column) {
                $entityData[$column] = $entity->$column;
            }
            $collectionData[] = $entityData;
        }
        return collect($collectionData);
    }

    public function hidePivot(Collection|LengthAwarePaginator $items, callable $hidePivotCallback): Collection|LengthAwarePaginator
    {
        if ($items instanceof LengthAwarePaginator) {
            // Transform paginated data while preserving pagination structure.
            $items->getCollection()->each(fn($item) => $hidePivotCallback($item));
        } else {
            // Transform regular collections.
            $items->each(fn($item) => $hidePivotCallback($item));
        }

        return $items;
    }

    public function hideModelPivot(Model $model, array $relationships): void
    {
        foreach ($relationships as $relationship) {
            // Split nested relationships (e.g., 'role.permissions').
            $nestedRelations = explode('.', $relationship);

            // Traverse the nested relationships.
            $current = $model;
            foreach ($nestedRelations as $relation) {
                if ($current && $current->$relation) {
                    $current = $current->$relation;
                } else {
                    // If the relationship doesn't exist, skip it.
                    $current = null;
                    break;
                }
            }

            // Hide the pivot attribute if the relationship exists and is a collection.
            if ($current && $current instanceof Collection) {
                $current->each->makeHidden('pivot');
            }
        }
    }

    public function prepareDataForMassAssignment(array $fillable, array $data): array
    {
        // Convert array to a collection for transformation
        return collect($data)
            ->only($fillable) // Retain only fillable attributes
            ->map(function ($value, $key) {
                // Example transformation: Trim strings and ensure null values are null
                return is_string($value) ? trim($value) : $value;
            })
            ->toArray(); // Convert back to an array
    }

    public function validateConditionParameter(array $conditionParameters)
    {
        $column = trim($conditionParameters[0]);
        $operator = trim($conditionParameters[1]);
        $value = trim($conditionParameters[2]);

        // Define allowed SQL operators to prevent injection
        $allowedOperators = ['=', '!=', '>', '<', '>=', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN'];

        // // Validate column to prevent SQL injection
        // if (!in_array($column, $this->getAvailableConditionColumns()) && !in_array($column, $this->getRelationshipKeysAliasis())) {
        //     throw new \InvalidArgumentException(
        //         "Invalid column name: $column - Valid columns are: " . implode(', ', $this->getAvailableConditionColumns())
        //     );
        // }

        // Fetch the relationship map once
        $relationshipMap = $this->getRelationshipMap();

        // Validate column to prevent SQL injection
        if (!in_array($column, $this->getAvailableConditionColumns()) && !isset($relationshipMap[$column])) {
            throw new \InvalidArgumentException(
                "Invalid column name: $column - Valid columns are: " . implode(', ', array_merge($this->getAvailableConditionColumns(), array_keys($relationshipMap)))
            );
        }


        // Validate operator to prevent injection
        if (!in_array(strtoupper($operator), $allowedOperators)) {
            throw new \InvalidArgumentException("Invalid operator: $operator - Valid operators are: " . implode(', ', $allowedOperators));
        }

        // Sanitize value (XSS protection)
        if (is_string($value)) {
            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }

        return [$column, $operator, $value];
    }
    /**
     * Applies filtering conditions dynamically, including handling relationship-based filtering.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query - The query builder instance.
     * @param array $conditions - List of filtering conditions (e.g., ['role:admin', 'status:active']).
     */
    public function applyConditions($query, $conditions)
    {
        if (!empty($conditions)) {
            foreach ($conditions as $condition) {
                // Split condition (e.g., 'role:admin' => ['role', '=', 'admin'])
                $parameters = $this->validateConditionParameter(explode(':', $condition));

                if (count($parameters) < 3) {
                    continue; // Skip invalid conditions
                }

                $column = $parameters[0]; // Column name (or alias)
                $operator = strtoupper($parameters[1]); // Standardize operator
                $value = $parameters[2]; // Condition value

                // Handle relationship keys (aliases like 'role' instead of 'role_id')
                // if (in_array($column, $this->getRelationshipKeysAliasis())) {
                //     $mappedIndex = array_search($column, $this->getRelationshipKeysAliasis());
                //     if ($mappedIndex !== false) {
                //         $actualColumn = $this->relationshipKeys[$mappedIndex]; // Map alias to actual column

                //         // Apply the condition to the related table using a join
                //         $query->whereHas(str_replace('_id', '', $actualColumn), function ($q) use ($value) {
                //             $q->where('name', $value); // Assuming the related table has a 'name' field
                //         });
                //     }
                // }
                // replace : Check if the column is an alias in the relationship map
                // Fetch the relationship map once
                $relationshipMap = $this->getRelationshipMap();
                if (isset($relationshipMap[$column])) {
                    $actualColumn = $relationshipMap[$column]; // Get actual column name

                    // Apply condition to the related table using a join
                    $query->whereHas(str_replace('_id', '', $actualColumn), function ($q) use ($value) {
                        $q->where('name', $value); // Assuming the related table has a 'name' field
                    });
                } else {
                    // Handle standard conditions
                    switch ($operator) {
                        case 'IN':
                            $query->whereIn($column, explode(',', $value));
                            break;
                        case 'NOT IN':
                            $query->whereNotIn($column, explode(',', $value));
                            break;
                        case 'BETWEEN':
                            $query->whereBetween($column, explode(',', $value));
                            break;
                        case 'NOT BETWEEN':
                            $query->whereNotBetween($column, explode(',', $value));
                            break;
                        case 'LIKE':
                            $query->where($column, 'LIKE', '%' . $value . '%');
                            break;
                        default:
                            $query->where($column, $operator, $value);
                            break;
                    }
                }
            }
        }
    }

    public function prepareRetreivedCollection(Collection|LengthAwarePaginator $records, array $validColumns): Collection|LengthAwarePaginator
    {
        // This method is called after the collection has been retrieved from the database
        // If no specific columns are requested, ensure relationships are loaded.
        if ($validColumns == ['*']) {
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
            return $validColumns !== ['*'] ? $this->getSpecificColumnsFromCollection($records, $validColumns) : $records;
        }
        // return empty collection:
        return $records;
    }
    public function prepareRetreivedModel(?object $record, array $validColumns): ?object
    {
        if (!$record) {
            return null;
        }
        // This method is called after the model has been retrieved from the database
        // If no specific columns are requested, ensure relationships are loaded.
        if ($validColumns == ['*']) {
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
        return $validColumns !== ['*'] ? $this->getSpecificColumnsFromSingleModel($record, $validColumns) : $record;
    }
}