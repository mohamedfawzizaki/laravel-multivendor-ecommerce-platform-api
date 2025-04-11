<?php

namespace App\Http\Controllers\Api\Admin\Address;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\ContinentService;
use App\Http\Responses\ApiResponse;

use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
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

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:256',
            ]);
            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("Continent updating validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }

            $continent = $this->continentService->create($validator->validated());

            // Return success response.
            return ApiResponse::success($continent, 'Continent created successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error creating continent: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|unique:continents,name|max:256',
                'columns'  => 'sometimes|array',
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("continent updating validation failed.", [
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

            $continent = $this->continentService->update($id, $validatedData, $columns);

            // Return success response.
            return ApiResponse::success($continent, 'Continent updated successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error updating continent: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function delete(ValidateColumnAndConditionRequest $request, string $id)
    {
        try {
            $forceDelete = $request->validated()['force'] ?? false;

            $continent = $this->continentService->delete($id, $forceDelete);

            return $forceDelete ?
                ApiResponse::success($continent, 'Continent permenantly deleted successfully.') :
                ApiResponse::success($continent, 'Continent soft deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting continent: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function isSoftDeleted(string $id)
    {
        try {
            $isDeleted = $this->continentService->softDeleted($id);

            return $isDeleted ?
                ApiResponse::success($isDeleted, 'Continent is soft deleted') :
                ApiResponse::success($isDeleted, 'Continent is not soft deleted');
        } catch (Exception $e) {
            Log::error("Error checking soft deleted continent: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function restore(ValidateColumnAndConditionRequest $request, string $id)
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];

            $continent = $this->continentService->restore($id, $columns);

            return ApiResponse::success($continent, 'Continent is restored');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error restoring soft deleted continent: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}