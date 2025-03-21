<?php

namespace App\Repositories;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

trait RepositoryHelperTrait
{
    /**
     * Filters and validates an array of column names against the allowed columns.
     *
     * This method ensures that only valid columns (defined in `availableColumns`) are included in the query.
     * If no valid columns are found, it logs an error and throws an exception.
     *
     * @param array $columns The columns to validate.
     * @return array The filtered array of valid columns.
     * @throws Exception If no valid columns are found.
     */
    public function getValidColumns(array $columns): array
    {
        // Filter the columns to include only those that are in the availableColumns list.
        $filteredColumns = array_filter($columns, function ($column) {
            return in_array($column, $this->getAvailableColumns());
        });

        // If no valid columns are found, log an error and throw an exception.
        if (empty($filteredColumns)) {
            Log::error('The specified columns in the query string are not valid.');
            throw new Exception(
                'The specified columns in the query string are not valid. Available columns are: ' .
                    implode(', ', $this->getAvailableColumns())
            );
        }

        // // Ensure we include necessary foreign keys if relationships exist
        // foreach ($this->relationshipKeys as $key) {
        //     if (!in_array($key, $filteredColumns) && in_array($key, $this->getAvailableColumns())) {
        //         $filteredColumns[] = $key; // Ensure foreign keys are included
        //     }
        // }

        return $filteredColumns;
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

    /**
     * Hides the pivot attribute from a collection or paginated result of models.
     *
     * This method processes a collection or paginated result and applies a callback function
     * to hide pivot attributes for each item. It preserves the pagination structure if the input is paginated.
     *
     * @param Collection|LengthAwarePaginator $items The items to process.
     * @param callable $hidePivotCallback A callback function to hide pivot attributes for a single model.
     * @return Collection|LengthAwarePaginator The processed items with hidden pivot attributes.
     */
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

    /**
     * Hides pivot attributes from a single model's relationships.
     *
     * This method processes a model instance and hides the pivot attribute for specified relationships.
     * It supports nested relationships (e.g., 'role.permissions') and skips relationships that do not exist.
     *
     * @param Model $model The model instance to process.
     * @param array $relationships An array of relationships to check for pivot attributes.
     * @return void
     */
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

        // Validate column to prevent SQL injection
        if (!in_array($column, $this->getAvailableConditionColumns())) {
            throw new \InvalidArgumentException("Invalid column name: $column - Valid columns are: " . implode(', ', $this->getAvailableConditionColumns()));
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

    public function applyConditions($query, $conditions)
    {
        if (!empty($conditions)) {
            foreach ($conditions as $condition) {
                $parameters = $this->validateConditionParameter(explode(':', $condition));
                $column = $parameters[0];
                $operator = strtoupper($parameters[1]); // Ensure consistency
                $value = $parameters[2];

                if ($operator === 'IN') {
                    $query->whereIn($column, explode(',', $value));
                } elseif ($operator === 'NOT IN') {
                    $query->whereNotIn($column, explode(',', $value));
                } elseif ($operator === 'BETWEEN') {
                    $query->whereBetween($column, explode(',', $value));
                } elseif ($operator === 'NOT BETWEEN') {
                    $query->whereNotBetween($column, explode(',', $value));
                } elseif ($operator === 'LIKE') {
                    $query->where($column, 'LIKE', '%' . $value . '%');
                } else {
                    $query->where($column, $operator, $value);
                }
            }
        }
    }
}