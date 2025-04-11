<?php

namespace App\Http\Controllers\Api\Vendor;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\PaginateRequest;
use Illuminate\Support\Facades\Validator;
use App\Services\Products\WarehouseService;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\ValidateColumnAndConditionRequest;


class WarehouseController extends Controller
{

    /**
     * Constructor to inject the WarehouseService dependency.
     *
     * @param WarehouseService $warehouseService The service responsible for warehouse-related operations.
     */
    public function __construct(protected WarehouseService $warehouseService) {}

    public function index(PaginateRequest $request): JsonResponse
    {
        try {
            if (Auth::user()->role == 'admin') {

                // Extract validated input values with default fallbacks.
                $validated = $request->validated();
                $paginate = $validated['paginate'] ?? false;
                $conditions = $validated['conditions'] ?? [];
                $columns = $validated['columns'] ?? ['*'];


                // var_dump($conditions);
                // Retrieve warehouses based on pagination preference.
                $warehouses = $paginate
                    ? $this->warehouseService->getAllWarehouses(
                        perPage: $validated['per_page'] ?? 15, // Default to 15 if not specified.
                        columns: $columns,
                        pageName: $validated['pageName'] ?? 'page',
                        page: $validated['page'] ?? 1,
                        conditions: $conditions
                    )
                    : $this->warehouseService->getAllWarehouses(
                        columns: $columns,
                        conditions: $conditions
                    );
            } else {
                // Extract validated input values with default fallbacks.
                $validated = $request->validated();
                $paginate = $validated['paginate'] ?? false;
                $columns = $validated['columns'] ?? ['*'];


                // Retrieve warehouses based on pagination preference.
                $warehouses = $paginate
                    ? $this->warehouseService->getAllWarehouses(
                        perPage: $validated['per_page'] ?? 15, // Default to 15 if not specified.
                        columns: $columns,
                        pageName: $validated['pageName'] ?? 'page',
                        page: $validated['page'] ?? 1,
                        conditions: ["vendor_id:=:" . Auth::user()->id]
                    )
                    : $this->warehouseService->getAllWarehouses(
                        columns: $columns,
                        conditions: ["vendor_id:=:" . Auth::user()->id]
                    );
            }

            if ($warehouses->isEmpty()) {
                return ApiResponse::success([], 'No warehouses found for the vendor.');
            }

            // Return a success response with the retrieved warehouses.
            return ApiResponse::success($warehouses, 'Warehouses retrieved successfully.');
        } catch (Exception $e) {
            // Handle any exceptions and return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function show(ValidateColumnAndConditionRequest $request, string $id): JsonResponse
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];

            $warehouse = $this->warehouseService->getWarehouseById($id, $columns);

            if (!$warehouse) {
                return ApiResponse::error('Warehouse not found', 404);
            }

            return ApiResponse::success($warehouse, 'Warehouse retrieved successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error retrieving warehouse: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function search(Request $request): JsonResponse
    {
        try {
            // Validate input parameters.
            $validator = Validator::make($request->all(), [
                'name' => 'required_without:email|string|exists:warehouses,name',
                'email' => 'required_without:name|email|exists:warehouses,email',
                'columns' => 'sometimes|array', // Optional columns parameter.
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("User retrieval validation failed.", [
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
            $searchBy = ($validated['email'] ?? null) ? 'email' : 'name';
            $columns = $validated['columns'] ?? ['*'];

            $warehouse = $this->warehouseService->searchBy($searchBy, $validated[$searchBy], $columns);

            // Return success response.
            return ApiResponse::success($warehouse, 'User retrieved successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error retrieving warehouse: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $warehouse = $this->warehouseService->create($data);

            return ApiResponse::success($warehouse, 'Warehouse retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error creating warehouse: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $warehouse = $this->warehouseService->getWarehouseById($id);

            if (!$warehouse) {
                return ApiResponse::error('Warehouse not found.', 404);
            }
            // Validate input parameters.
            $validator = Validator::make($request->all(), [
                'vendor_id' => 'sometimes|exists:users,id',
                'name'      => 'sometimes|string|unique:warehouses,name',
                'email'     => 'sometimes|email|unique:warehouses,email',
                'phone'     => 'sometimes|string|regex:/^[\d\s\+\-]+$/',
                'city_id'   => 'sometimes|exists:cities,id',
                'address'   => 'sometimes|string',
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("Warehouse updating validation failed.", [
                    'errors' => $validator->errors(),
                    'input' => $request->all(), // Log the provided input for debugging.
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }

            $data = $validator->validated();

            if (Auth::user()->role == 'admin') {
                $warehouse = $this->warehouseService->update($id, $data);
            } else {
                if (Auth::user()->id !== $warehouse->vendor_id) {
                    return ApiResponse::error('Vendor can only update his warehouses');
                }

                if (isset($data['vendor_id'])) {
                    unset($data['vendor_id']);
                }

                $warehouse = $this->warehouseService->update($id, $data);
            }

            return ApiResponse::success($warehouse, 'Warehouse updated successfully.');
        } catch (Exception $e) {
            Log::error("Warehouse update system error", [
                'warehouse_id' => $id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ApiResponse::error(
                $e->getMessage(),
                500
            );
        }
    }

    public function updateBulk(Request $request): JsonResponse
    {
        try {
            // Validate input parameters.
            $validator = Validator::make($request->all(), [
                'vendor_id' => 'sometimes|exists:users,id',
                'phone'     => 'sometimes|string|regex:/^[\d\s\+\-]+$/',
                'city_id'   => 'sometimes|exists:cities,id',
                'address'   => 'sometimes|string',

                'conditions'  => 'sometimes|array',
                'columns'     => 'sometimes|array',
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("Warehouse updating validation failed.", [
                    'errors' => $validator->errors(),
                    'input' => $request->all(), // Log the provided input for debugging.
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }

            $validated = $validator->validated();

            $mainConditions = $validator->validated()['conditions'] ?? [];
            $columns = $validator->validated()['columns'] ?? ['*'];
            // Filter only valid product fields (excluding 'columns' , 'conditions')
            $data = array_filter($validated, function ($key) {
                return !in_array($key, ['columns', 'conditions']); // Exclude 'columns' and 'conditions' key and conditions key
            }, ARRAY_FILTER_USE_KEY);

            if (Auth::user()->role == 'admin') {
                $warehouses = $this->warehouseService->updateGroup($data, $mainConditions, $columns);
            } else {
                if (isset($data['vendor_id'])) {
                    unset($data['vendor_id']);
                }

                $conditions = $mainConditions;
                $conditions[] = "vendor_id:=:" . $request->user()->id;

                $warehouses = $this->warehouseService->updateGroup($data, $conditions, $columns);
            }
            // Check if the vendor has any warehouses.
            if (empty($warehouses)) {
                return ApiResponse::error('There are no warehouses.', 404);
            }
            // Return success response.
            return ApiResponse::success($warehouses, 'Warehouse updated successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error updating warehouse: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function delete(ValidateColumnAndConditionRequest $request, string $id)
    {
        try {
            $warehouse = $this->warehouseService->getWarehouseById($id);

            if ($request->user()->id !== $warehouse->vendor_id && $request->user()->role !== 'admin') {
                return ApiResponse::error('Vendor can only delete his warehouses discounts');
            }

            $deleted = $this->warehouseService->delete($id, true);
            
            return ApiResponse::success([], 'Warehouse deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting warehouse: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function deleteBulk(ValidateColumnAndConditionRequest $request)
    {
        try {
            $conditions = $request->validated()['conditions'] ?? [];

            if (Auth::user()->role == 'admin') {
                $deletedWarehouses = $this->warehouseService->deleteBulk($conditions, true);
            } else {
                $conditions[] = "vendor_id:=:" . $request->user()->id;
                $deletedWarehouses = $this->warehouseService->deleteBulk($conditions, true);
            }

            return ApiResponse::success($deletedWarehouses, 'Warehouses deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting warehouses: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}