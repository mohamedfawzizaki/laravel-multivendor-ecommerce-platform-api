<?php

namespace App\Http\Controllers\Api\User;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\CityService;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;

use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaginateRequest;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreAddressRequest;

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

    public function show(Request $request, string $id): JsonResponse
    {
        try {
            // Merge request data with the provided ID for validation.
            $data = array_merge($request->all(), ['id' => $id]);

            // Validate input parameters.
            $validator = Validator::make($data, [
                'id' => 'required|string',
                'columns' => 'sometimes|array', // Optional columns parameter.
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("City retrieval validation failed.", [
                    'errors' => $validator->errors(),
                    'input' => $data, // Log the provided input for debugging.
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }

            // Extract validated data.
            $validated = $validator->validated();
            $columns = $validated['columns'] ?? ['*'];

            // Retrieve the city.
            $city = $this->cityService->getCityById($validated['id'], $columns);

            // Return success response.
            return ApiResponse::success($city, 'City retrieved successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error retrieving city: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function searchBy(Request $request): JsonResponse
    {
        try {
            // Validate input parameters.
            $validator = Validator::make($request->all(), [
                'city' => 'required|string|exists:citys,city',
                'columns' => 'sometimes|array', // Optional columns parameter.
            ], [
                'city' => 'the selected city is invalid or is not found'
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("City retrieval validation failed.", [
                    'errors' => $validator->errors(),
                    'input' => $request->all(), // Log the provided input for debugging.
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }

            // Extract validated data.
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
            // Merge request data with the provided ID for validation.
            $idAndColumns = array_merge($request->all(), ['id' => $id]);
            $validatorForidAndColumns = Validator::make($idAndColumns, [
                'id' => 'required|string',
                'columns'  => 'sometimes|array',
            ]);
            // Handle validation failures.
            if ($validatorForidAndColumns->fails()) {
                Log::warning("City updating validation failed.", [
                    'errors' => $validatorForidAndColumns->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validatorForidAndColumns->errors()
                );
            }
            // Validate input parameters.
            $validatorForDataToUpdate = Validator::make($request->all(), [
                'city' => 'sometimes|string|unique:citys,city|max:30',
                'is_primary' => 'sometimes|boolean',
            ]);

            // Handle validation failures.
            if ($validatorForDataToUpdate->fails()) {
                Log::warning("City updating validation failed.", [
                    'errors' => $validatorForDataToUpdate->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validatorForDataToUpdate->errors()
                );
            }
            // Extract validated data.
            $validatedData = $validatorForDataToUpdate->validated();
            $id = $validatorForidAndColumns->validated()['id'];
            $columns = $validatorForidAndColumns->validated()['columns'] ?? ['*'];

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

    public function updateBulk(Request $request): JsonResponse
    {
        try {
            // Validate input parameters.
            $validator = Validator::make($request->all(), [
                'created_at'  => 'sometimes|date',
                'updated_at'  => 'sometimes|date',
                'deleted_at'  => 'sometimes|date',

                'conditions'  => 'sometimes|array',
                'columns'     => 'sometimes|array',
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("Citys updating validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            };
            $validated = $validator->validated();

            $conditions = $validator->validated()['conditions'] ?? [];
            $columns = $validator->validated()['columns'] ?? ['*'];

            // Convert created_at & updated_at if provided
            if (!empty($validated['created_at'])) {
                $validated['created_at'] = Carbon::parse($validated['created_at'])->format('Y-m-d H:i:s');
            }

            if (!empty($validated['updated_at'])) {
                $validated['updated_at'] = Carbon::parse($validated['updated_at'])->format('Y-m-d H:i:s');
            }

            if (!empty($validated['deleted_at'])) {
                $validated['deleted_at'] = Carbon::parse($validated['deleted_at'])->format('Y-m-d H:i:s');
            }

            // Filter only valid city fields (excluding 'columns')
            $data = array_filter($validated, function ($key) {
                return !in_array($key, ['columns', 'conditions']); // Exclude 'columns' key and conditions key
            }, ARRAY_FILTER_USE_KEY);

            // Call update function with or without columns
            $citys = $this->cityService->updateGroup($data, $conditions, $columns);

            // Return success response.
            return ApiResponse::success($citys, 'City updated successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error updating city: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function delete(Request $request, string $id)
    {
        try {
            // Merge request data with the provided ID for validation.
            $data = array_merge($request->all(), ['id' => $id]);
            // Validate input parameters.
            $validator = Validator::make($data, [
                'id' => 'required|string',
                'force' => 'sometimes|accepted',
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
            $validated = $validator->validated();
            $forceDelete = $validated['force'] ?? false;

            $city = $this->cityService->delete($validated['id'], $forceDelete);

            // Return success response.
            return $forceDelete ?
                ApiResponse::success($city, 'City permenantly deleted successfully.') :
                ApiResponse::success($city, 'City soft deleted successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error deleting city: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
    public function deleteBulk(Request $request)
    {
        try {
            // Validate input parameters.
            $validator = Validator::make($request->all(), [
                'conditions'  => 'required|array',
                'force' => 'sometimes|accepted',
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("Citys deletion validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }
            // Extract validated data.
            $validated = $validator->validated();
            $conditions = $validated['conditions'] ?? false;
            $forceDelete = $validated['force'] ?? false;

            $deletedCitys = $this->cityService->deleteBulk($conditions, $forceDelete);

            // Return success response.
            return $forceDelete ?
                ApiResponse::success($deletedCitys, 'Citys permenantly deleted successfully.') :
                ApiResponse::success($deletedCitys, 'Citys soft deleted successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error deleting citys: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function isSoftDeleted(string $id)
    {
        try {
            $validator = Validator::make(['id' => $id], [
                'id' => 'required|string'
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("City checking validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }

            $id = $validator->validated()['id'];

            $isDeleted = $this->cityService->softDeleted($id);

            return $isDeleted ?
                ApiResponse::success($isDeleted, 'City is soft deleted') :
                ApiResponse::success($isDeleted, 'City is not soft deleted');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error checking soft deleted city: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
    public function restore(Request $request, string $id)
    {
        try {
            // Merge request data with the provided ID for validation.
            $data = array_merge($request->all(), ['id' => $id]);
            $validator = Validator::make($data, [
                'id' => 'required|string',
                'columns'  => 'sometimes|array',
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("City restoring validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }

            $id = $validator->validated()['id'];
            $columns = $validator->validated()['columns'] ?? ['*'];

            $city = $this->cityService->restore($id, $columns);

            return ApiResponse::success($city, 'City is restored');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error restoring soft deleted city: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
    public function restoreBulk(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'conditions'  => 'sometimes|array',
                'columns'  => 'sometimes|array',
            ]);

            if ($validator->fails()) {
                Log::warning("Citys restoring validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }

            $conditions = $validator->validated()['conditions'] ?? [];
            $columns = $validator->validated()['columns'] ?? ['*'];

            $citys = $this->cityService->restoreBulk($conditions, $columns);

            return ApiResponse::success($citys, 'City is restored');
        } catch (Exception $e) {
            Log::error("Error restoring citys: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}
