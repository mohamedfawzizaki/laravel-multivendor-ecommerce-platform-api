<?php

namespace App\Http\Controllers\Api\Vendor\Warehouses;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Warehouses\Warehouse;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\PaginateRequest;
use Illuminate\Support\Facades\Validator;
use App\Services\Warehouses\WarehouseService;
use App\Http\Requests\Warehouses\StoreWarehouseRequest;
use App\Http\Requests\ValidateColumnAndConditionRequest;
use App\Http\Requests\Warehouses\UpdateWarehouseRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
                    ? $this->warehouseService->getAll(
                        perPage: $validated['per_page'] ?? 15, // Default to 15 if not specified.
                        columns: $columns,
                        pageName: $validated['pageName'] ?? 'page',
                        page: $validated['page'] ?? 1,
                        conditions: $conditions
                    )
                    : $this->warehouseService->getAll(
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
                    ? $this->warehouseService->getAll(
                        perPage: $validated['per_page'] ?? 15, // Default to 15 if not specified.
                        columns: $columns,
                        pageName: $validated['pageName'] ?? 'page',
                        page: $validated['page'] ?? 1,
                        conditions: ["vendor_id:=:" . Auth::user()->id]
                    )
                    : $this->warehouseService->getAll(
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

            $warehouse = $this->warehouseService->getById($id, $columns);

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

    public function search(string $query): JsonResponse
    {
        try {
            // Validate the incoming request
            $validator = Validator::make(['query' => $query], [
                'query' => 'required|string', // The search value must be a string
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Invalid request parameters.', 400);
            }
            // Validate the incoming request
            $value = $validator->validated()['query'];

            $result = Warehouse::whereAny([
                'code',
                'name',
                'contact_name',
                'contact_email',
                'total_capacity',
                'city_id',
                'status'
            ], 'like', '%' . $value . '%')->get();

            // Check if the result is empty
            if ($result->isEmpty()) {
                return ApiResponse::error('No matching warehouses found.', 404);
            }

            return ApiResponse::success($result, 'warehouses found.');
        } catch (ModelNotFoundException $e) {
            Log::warning("warehouses search failed: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error('warehouses not found.', 404);
        } catch (Exception $e) {
            Log::error("Error searching warehouses: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error('An error occurred while searching for warehouses.', 500);
        }
    }


    public function location(Request $request, string $id): JsonResponse
    {
        try {
            // $warehouse = Warehouse::find($id);
            $warehouse = Warehouse::with('city.country')->find($id);


            if (!$warehouse) {
                return ApiResponse::error('Warehouse not found.', 404);
            }

            if ($request->user()->id !== $warehouse->vendor_id && $request->user()->role !== 'admin') {
                return ApiResponse::error('Vendor can only review his warehouses location');
            }

            $location = "{$warehouse->city->name}" . " -> {$warehouse->city->country->name}" . " -> {$warehouse->city->country->continent->name}";

            return ApiResponse::success($location, 'warehouse location.');
        } catch (ModelNotFoundException $e) {
            Log::warning("warehouses search failed: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error('warehouses not found.', 404);
        } catch (Exception $e) {
            Log::error("Error searching warehouses: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error('An error occurred while searching for warehouses.', 500);
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

    public function update(UpdateWarehouseRequest $request, string $id): JsonResponse
    {
        try {
            $warehouse = $this->warehouseService->getById($id);

            if (!$warehouse) {
                return ApiResponse::error('Warehouse not found.', 404);
            }

            $data = $request->validated();

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

    public function delete(ValidateColumnAndConditionRequest $request, string $id)
    {
        try {
            $warehouse = $this->warehouseService->getById($id);

            if (!$warehouse) {
                return ApiResponse::error('Warehouse not found.', 404);
            }

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
}