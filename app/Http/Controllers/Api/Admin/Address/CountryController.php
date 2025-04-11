<?php

namespace App\Http\Controllers\Api\Admin\Address;

use Exception;
use Illuminate\Http\Request;
use App\Services\CountryService;

use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;

use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\ValidateColumnAndConditionRequest;

class CountryController extends Controller
{
    /**
     * Constructor to inject the CountryService dependency.
     *
     * @param CountryService $countryService The service responsible for country-related operations.
     */
    public function __construct(protected CountryService $countryService) {}

    public function store(StoreAddressRequest $request): JsonResponse
    {
        try {
            // Extract validated data.
            $validated = $request->validated();

            $country = $this->countryService->create($validated);

            // Return success response.
            return ApiResponse::success($country, 'Country created successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error creating country: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|unique:countries,name|max:256',
                'continent_id' => 'sometimes|string|exists:continents,id',
                'columns'  => 'sometimes|array',
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("country updating validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }
            // Extract validated data.
            $validatedData = $request->except(['columns']);
            $columns = empty($request->only(['columns'])) ? ['*'] : $request->only(['columns']);

            $country = $this->countryService->update($id, $validatedData, $columns);

            // Return success response.
            return ApiResponse::success($country, 'Country updated successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error updating country: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function delete(ValidateColumnAndConditionRequest $request, string $id)
    {
        try {
            $forceDelete = $request->validated()['force'] ?? false;

            $country = $this->countryService->delete($id, $forceDelete);

            return $forceDelete ?
                ApiResponse::success($country, 'Country permenantly deleted successfully.') :
                ApiResponse::success($country, 'Country soft deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting country: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function isSoftDeleted(string $id)
    {
        try {
            $isDeleted = $this->countryService->softDeleted($id);

            return $isDeleted ?
                ApiResponse::success($isDeleted, 'Country is soft deleted') :
                ApiResponse::success($isDeleted, 'Country is not soft deleted');
        } catch (Exception $e) {
            Log::error("Error checking soft deleted country: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function restore(ValidateColumnAndConditionRequest $request, string $id)
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];

            $country = $this->countryService->restore($id, $columns);

            return ApiResponse::success($country, 'Country is restored');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error restoring soft deleted country: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}