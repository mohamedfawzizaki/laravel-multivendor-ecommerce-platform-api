<?php

namespace App\Http\Controllers\Api\Admin\Address;

use Exception;
use Illuminate\Http\Request;
use App\Services\CityService;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\ValidateColumnAndConditionRequest;

class CityController extends Controller
{
    /**
     * Constructor to inject the CityService dependency.
     *
     * @param CityService $cityService The service responsible for city-related operations.
     */
    public function __construct(protected CityService $cityService) {}

    public function store(StoreAddressRequest $request): JsonResponse
    {
        try {
            // Extract validated data.
            $validated = $request->validated();

            $city = $this->cityService->create($validated);

            // Return success response.
            return ApiResponse::success($city, 'City created successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error creating city: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|unique:cities,name|max:256',
                'country_id' => 'sometimes|string|exists:countries,id',
                'columns'  => 'sometimes|array',
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("City updating validation failed.", [
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

            $city = $this->cityService->update($id, $validatedData, $columns);

            // Return success response.
            return ApiResponse::success($city, 'City updated successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error updating city: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function delete(ValidateColumnAndConditionRequest $request, string $id)
    {
        try {
            $forceDelete = $request->validated()['force'] ?? false;

            $city = $this->cityService->delete($id, $forceDelete);

            return $forceDelete ?
                ApiResponse::success($city, 'City permenantly deleted successfully.') :
                ApiResponse::success($city, 'City soft deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting city: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function isSoftDeleted(string $id)
    {
        try {
            $isDeleted = $this->cityService->softDeleted($id);

            return $isDeleted ?
                ApiResponse::success($isDeleted, 'City is soft deleted') :
                ApiResponse::success($isDeleted, 'City is not soft deleted');
        } catch (Exception $e) {
            Log::error("Error checking soft deleted city: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function restore(ValidateColumnAndConditionRequest $request, string $id)
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];

            $city = $this->cityService->restore($id, $columns);

            return ApiResponse::success($city, 'City is restored');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error restoring soft deleted city: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}