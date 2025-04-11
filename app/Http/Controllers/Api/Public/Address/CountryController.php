<?php

namespace App\Http\Controllers\Api\Public\Address;

use Exception;
use App\Services\CountryService;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;

use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaginateRequest;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ValidateColumnAndConditionRequest;

class CountryController extends Controller
{
    /**
     * Constructor to inject the CountryService dependency.
     *
     * @param CountryService $countryService The service responsible for country-related operations.
     */
    public function __construct(protected CountryService $countryService) {}

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
            // Retrieve countrys based on pagination preference.
            $countrys = $paginate
                ? $this->countryService->getAllCountrys(
                    perPage: $validated['per_page'] ?? 15, // Default to 15 if not specified.
                    columns: $columns,
                    pageName: $validated['pageName'] ?? 'page',
                    page: $validated['page'] ?? 1,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                )
                : $this->countryService->getAllCountrys(
                    columns: $columns,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                );

            // Return a success response with the retrieved countrys.
            return ApiResponse::success($countrys, 'Countrys retrieved successfully.');
        } catch (Exception $e) {
            // Handle any exceptions and return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function show(ValidateColumnAndConditionRequest $request, string $id): JsonResponse
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];

            $country = $this->countryService->getCountryById($id, $columns);

            return ApiResponse::success($country, 'Country retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving country: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function search(string $countryName): JsonResponse
    {
        try {
            // Validate input parameters.
            $validator = Validator::make(['name' => $countryName], [
                'name' => 'required|string|exists:countries,name',
                'columns' => 'sometimes|array', // Optional columns parameter.
            ], [
                'name' => 'the selected country is invalid or is not found'
            ]);

            if ($validator->fails()) {
                Log::warning("Country retrieval validation failed.", [
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

            $country = $this->countryService->searchBy('name', $validated['name'], $columns);

            // Return success response.
            return ApiResponse::success($country, 'Country retrieved successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error retrieving country: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}