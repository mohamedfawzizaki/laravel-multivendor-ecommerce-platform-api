<?php

namespace App\Http\Controllers\Api\Public\Address;

use Exception;
use Illuminate\Http\JsonResponse;
use App\Services\ContinentService;
use App\Http\Responses\ApiResponse;

use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaginateRequest;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ValidateColumnAndConditionRequest;

class ContinentController extends Controller
{
    /**
     * Constructor to inject the ContinentService dependency.
     *
     * @param ContinentService $continentService The service responsible for continent-related operations.
     */
    public function __construct(protected ContinentService $continentService) {}

    public function index(PaginateRequest $request): JsonResponse
    {

        try {
            // Extract validated input values with default fallbacks.
            $validated = $request->validated();
            $paginate = $validated['paginate'] ?? false;
            $withTrashed = $validated['with_trashed'] ?? false;
            $onlyTrashed = $validated['only_trashed'] ?? false;
            $conditions = $validated['conditions'] ?? [];
            $columns = $validated['columns'] ?? ['*'];

            // Retrieve continents based on pagination preference.
            $continents = $paginate
                ? $this->continentService->getAllContinents(
                    perPage: $validated['per_page'] ?? 15, // Default to 15 if not specified.
                    columns: $columns,
                    pageName: $validated['pageName'] ?? 'page',
                    page: $validated['page'] ?? 1,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                )
                : $this->continentService->getAllContinents(
                    columns: $columns,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                );

            // Return a success response with the retrieved continents.
            return ApiResponse::success($continents, 'Continents retrieved successfully.');
        } catch (Exception $e) {
            // Handle any exceptions and return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function show(ValidateColumnAndConditionRequest $request, string $id): JsonResponse
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];

            // Retrieve the continent.
            $continent = $this->continentService->getContinentById($id, $columns);

            // Return success response.
            return ApiResponse::success($continent, 'Continent retrieved successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error retrieving continent: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function search(string $continentName): JsonResponse
    {
        try {
            // Validate input parameters.
            $validator = Validator::make(['name' => $continentName], [
                'name' => 'required|string|exists:continents,name',
                'columns' => 'sometimes|array', // Optional columns parameter.
            ], [
                'name' => 'the selected continent is invalid or is not found'
            ]);

            if ($validator->fails()) {
                Log::warning("Continent retrieval validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }

            $validated = $validator->validated();
            $columns = $validated['columns'] ?? ['*'];

            $continent = $this->continentService->searchBy('name', $validated['name'], $columns);

            // Return success response.
            return ApiResponse::success($continent, 'Continent retrieved successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error retrieving continent: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}