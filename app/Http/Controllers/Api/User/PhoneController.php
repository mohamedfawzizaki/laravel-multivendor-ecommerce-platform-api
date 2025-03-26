<?php

namespace App\Http\Controllers\Api\User;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\PhoneService;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaginateRequest;
use App\Http\Requests\StorePhoneRequest;
use Illuminate\Support\Facades\Validator;

class PhoneController extends Controller
{
    /**
     * Constructor to inject the PhoneService dependency.
     *
     * @param PhoneService $phoneService The service responsible for phone-related operations.
     */
    public function __construct(protected PhoneService $phoneService) {}

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
            // Retrieve phones based on pagination preference.
            $phones = $paginate
                ? $this->phoneService->getAllPhones(
                    perPage: $validated['per_page'] ?? 15, // Default to 15 if not specified.
                    columns: $columns,
                    pageName: $validated['pageName'] ?? 'page',
                    page: $validated['page'] ?? 1,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                )
                : $this->phoneService->getAllPhones(
                    columns: $columns,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                );

            // Return a success response with the retrieved phones.
            return ApiResponse::success($phones, 'Phones retrieved successfully.');
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
                Log::warning("Phone retrieval validation failed.", [
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

            // Retrieve the phone.
            $phone = $this->phoneService->getPhoneById($validated['id'], $columns);

            // Return success response.
            return ApiResponse::success($phone, 'Phone retrieved successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error retrieving phone: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function searchBy(Request $request): JsonResponse
    {
        try {
            // Validate input parameters.
            $validator = Validator::make($request->all(), [
                'phone' => 'required|string|exists:phones,phone',
                'columns' => 'sometimes|array', // Optional columns parameter.
            ], [
                'phone'=>'the selected phone is invalid or is not found'
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("Phone retrieval validation failed.", [
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

            $phone = $this->phoneService->searchBy('name', $validated['name'], $columns);

            // Return success response.
            return ApiResponse::success($phone, 'Phone retrieved successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error retrieving phone: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function store(StorePhoneRequest $request): JsonResponse
    {
        try {
            // Extract validated data.
            $validated = $request->validated();

            $phone = $this->phoneService->create($validated);

            // Return success response.
            return ApiResponse::success($phone, 'Phone created successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error creating phone: {$e->getMessage()}", ['exception' => $e]);

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
                Log::warning("Phone updating validation failed.", [
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
                'phone' => 'sometimes|string|unique:phones,phone|max:30',
                'is_primary' => 'sometimes|boolean',
            ]);

            // Handle validation failures.
            if ($validatorForDataToUpdate->fails()) {
                Log::warning("Phone updating validation failed.", [
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

            $phone = $this->phoneService->update($id, $validatedData, $columns);

            // Return success response.
            return ApiResponse::success($phone, 'Phone updated successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error updating phone: {$e->getMessage()}", ['exception' => $e]);

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
                Log::warning("Phones updating validation failed.", [
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

            // Filter only valid phone fields (excluding 'columns')
            $data = array_filter($validated, function ($key) {
                return !in_array($key, ['columns', 'conditions']); // Exclude 'columns' key and conditions key
            }, ARRAY_FILTER_USE_KEY);

            // Call update function with or without columns
            $phones = $this->phoneService->updateGroup($data, $conditions, $columns);

            // Return success response.
            return ApiResponse::success($phones, 'Phone updated successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error updating phone: {$e->getMessage()}", ['exception' => $e]);

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
                Log::warning("Phone updating validation failed.", [
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

            $phone = $this->phoneService->delete($validated['id'], $forceDelete);

            // Return success response.
            return $forceDelete ?
                ApiResponse::success($phone, 'Phone permenantly deleted successfully.') :
                ApiResponse::success($phone, 'Phone soft deleted successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error deleting phone: {$e->getMessage()}", ['exception' => $e]);

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
                Log::warning("Phones deletion validation failed.", [
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

            $deletedPhones = $this->phoneService->deleteBulk($conditions, $forceDelete);

            // Return success response.
            return $forceDelete ?
                ApiResponse::success($deletedPhones, 'Phones permenantly deleted successfully.') :
                ApiResponse::success($deletedPhones, 'Phones soft deleted successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error deleting phones: {$e->getMessage()}", ['exception' => $e]);

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
                Log::warning("Phone checking validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }

            $id = $validator->validated()['id'];

            $isDeleted = $this->phoneService->softDeleted($id);

            return $isDeleted ?
                ApiResponse::success($isDeleted, 'Phone is soft deleted') :
                ApiResponse::success($isDeleted, 'Phone is not soft deleted');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error checking soft deleted phone: {$e->getMessage()}", ['exception' => $e]);

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
                Log::warning("Phone restoring validation failed.", [
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

            $phone = $this->phoneService->restore($id, $columns);

            return ApiResponse::success($phone, 'Phone is restored');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error restoring soft deleted phone: {$e->getMessage()}", ['exception' => $e]);

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
                Log::warning("Phones restoring validation failed.", [
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

            $phones = $this->phoneService->restoreBulk($conditions, $columns);

            return ApiResponse::success($phones, 'Phone is restored');
        } catch (Exception $e) {
            Log::error("Error restoring phones: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }


}