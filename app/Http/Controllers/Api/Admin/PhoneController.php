<?php

namespace App\Http\Controllers\Api\Admin;

use Exception;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Services\PhoneService;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaginateRequest;
use App\Http\Requests\StorePhoneRequest;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ValidateColumnAndConditionRequest;

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

    public function show(ValidateColumnAndConditionRequest $request, string $id): JsonResponse
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];

            // Validate the ID format (either UUID for phone_id or user_id)
            if (Str::isUuid($id)) {
                $phone =  $this->phoneService->searchBy('user_id', $id, $columns);
            } else {
                $phone = $this->phoneService->getPhoneById($id, $columns);
            }

            if (!$phone) {
                return ApiResponse::error('Phone not found', 404);
            }

            return ApiResponse::success($phone, 'Phone retrieved successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error retrieving phone: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function userPhones(ValidateColumnAndConditionRequest $request, string $id): JsonResponse
    {
        try {
            // Extract validated input values with default fallbacks.
            $validated = $request->validated();
            // var_dump($conditions);
            // Retrieve phones based on pagination preference.
            $phones = $this->phoneService->getAllPhones(
                    columns: $validated['columns'] ?? ['*'],
                    conditions: ["user_id:=:{$id}"]
                );

            // Return a success response with the retrieved phones.
            return ApiResponse::success($phones, 'Phones retrieved successfully.');
        } catch (Exception $e) {
            // Handle any exceptions and return an error response.
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

            $phone = $this->phoneService->searchBy('phone', $validated['phone'], $columns);

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
            $phone = $this->phoneService->create([
                'phone'=>$request->validated()['phone'],
                'is_primary'=>$request->validated()['is_primary'],
                'user_id'=>$request->user()->id,
            ]);

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
            $validator = Validator::make($request->all(), [
                'phone' => 'sometimes|string|unique:phones,phone|max:30',
                'is_primary' => 'sometimes|boolean',
                'columns'  => 'sometimes|array',
            ]);

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
            $validatedData = $request->except(['columns']);
            $columns = empty($request->only(['columns'])) ? ['*'] : $request->only(['columns']);

            $phone = $this->phoneService->update($id, $validatedData, $columns);

            return ApiResponse::success($phone, 'Phone updated successfully.');
        } catch (Exception $e) {
            Log::error("Error updating phone: {$e->getMessage()}", ['exception' => $e]);
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

    public function delete(ValidateColumnAndConditionRequest $request, string $id)
    {
        try {
            $forceDelete = $request->validated()['force'] ?? false;

            $phone = $this->phoneService->delete($id, $forceDelete);

            return $forceDelete ?
                ApiResponse::success($phone, 'Phone permenantly deleted successfully.') :
                ApiResponse::success($phone, 'Phone soft deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting phone: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function deleteBulk(ValidateColumnAndConditionRequest $request)
    {
        try {
            $conditions = $request->validated()['conditions'] ?? [];
            $forceDelete = $request->validated()['force'] ?? false;

            $deletedPhones = $this->phoneService->deleteBulk($conditions, $forceDelete);

            return $forceDelete ?
                ApiResponse::success($deletedPhones, 'Phones permenantly deleted successfully.') :
                ApiResponse::success($deletedPhones, 'Phones soft deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting phones: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function isSoftDeleted(string $id)
    {
        try {
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
    
    public function restore(ValidateColumnAndConditionRequest $request, string $id)
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];

            $phone = $this->phoneService->restore($id, $columns);

            return ApiResponse::success($phone, 'Phone is restored');
        } catch (Exception $e) {
            Log::error("Error restoring soft deleted phone: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
    
    public function restoreBulk(ValidateColumnAndConditionRequest $request)
    {
        try {
            $conditions = $request->validated()['conditions'] ?? [];
            $columns = $request->validated()['columns'] ?? ['*'];

            $phones = $this->phoneService->restoreBulk($conditions, $columns);

            return ApiResponse::success($phones, 'Phone is restored');
        } catch (Exception $e) {
            Log::error("Error restoring phones: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}