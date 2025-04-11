<?php

namespace App\Http\Controllers\Api\Public\Address;

use Exception;
use App\Services\CityService;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaginateRequest;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ValidateColumnAndConditionRequest;

class CityController extends Controller
{
    /**
     * Constructor to inject the CityService dependency.
     *
     * @param CityService $cityService The service responsible for city-related operations.
     */
    public function __construct(protected CityService $cityService) {}

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


            // var_dump($conditions);
            // Retrieve citys based on pagination preference.
            $citys = $paginate
                ? $this->cityService->getAllCitys(
                    perPage: $validated['per_page'] ?? 15, // Default to 15 if not specified.
                    columns: $columns,
                    pageName: $validated['pageName'] ?? 'page',
                    page: $validated['page'] ?? 1,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                )
                : $this->cityService->getAllCitys(
                    columns: $columns,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                );

            // Return a success response with the retrieved citys.
            return ApiResponse::success($citys, 'Citys retrieved successfully.');
        } catch (Exception $e) {
            // Handle any exceptions and return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function show(ValidateColumnAndConditionRequest $request, string $id): JsonResponse
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];

            // Retrieve the city.
            $city = $this->cityService->getCityById($id, $columns);

            // Return success response.
            return ApiResponse::success($city, 'City retrieved successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error retrieving city: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function search(string $cityName): JsonResponse
    {
        try {
            // Validate input parameters.
            $validator = Validator::make(['name' => $cityName], [
                'name' => 'required|string|exists:cities,name',
                'columns' => 'sometimes|array', // Optional columns parameter.
            ], [
                'name' => 'the selected city is invalid or is not found'
            ]);

            if ($validator->fails()) {
                Log::warning("City retrieval validation failed.", [
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

            $city = $this->cityService->searchBy('name', $validated['name'], $columns);

            // Return success response.
            return ApiResponse::success($city, 'City retrieved successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error retrieving city: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}