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
use App\Models\Warehouses\WarehouseZone;
use Illuminate\Support\Facades\Validator;
use App\Services\Warehouses\WarehouseZoneService;
use App\Http\Requests\ValidateColumnAndConditionRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\Warehouses\StoreWarehouseZoneRequest;
use App\Http\Requests\Warehouses\UpdateWarehouseZoneRequest;

class WarehouseZoneController extends Controller
{
    /**
     * Constructor to inject the WarehouseZoneService dependency.
     *
     * @param WarehouseZoneService $warehouseZoneService The service responsible for warehouse-related operations.
     */
    public function __construct(protected WarehouseZoneService $warehouseZoneService) {}

    public function index(PaginateRequest $request, string $warehouseId): JsonResponse
    {
        try {
            $validated = $request->validated();
            $paginate = $validated['paginate'] ?? false;
            $columns = $validated['columns'] ?? ['*'];
            $conditions = ["warehouse_id:=:$warehouseId"];

            // If user is not admin, validate access to the warehouse
            if (Auth::user()->role !== 'admin') {
                $warehouse = Warehouse::find($warehouseId);

                if (!$warehouse) {
                    return ApiResponse::error('The warehouse was not found.', 404);
                }

                if (Auth::id() !== $warehouse->vendor_id) {
                    return ApiResponse::error('You are not authorized to access this warehouse.', 403);
                }
            }

            // Retrieve warehouse zones based on pagination preference
            $warehouseZones = $paginate
                ? $this->warehouseZoneService->getAll(
                    perPage: $validated['per_page'] ?? 15,
                    columns: $columns,
                    pageName: $validated['pageName'] ?? 'page',
                    page: $validated['page'] ?? 1,
                    conditions: $conditions
                )
                : $this->warehouseZoneService->getAll(
                    columns: $columns,
                    conditions: $conditions
                );

            if ($warehouseZones->isEmpty()) {
                return ApiResponse::success([], 'No warehouse zones found.');
            }

            return ApiResponse::success($warehouseZones, 'Warehouse zones retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving warehouse zones: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error('An error occurred while retrieving warehouse zones.', 500);
        }
    }

    public function show(ValidateColumnAndConditionRequest $request, string $zoneId): JsonResponse
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];

            $warehouseZone = $this->warehouseZoneService->getById($zoneId, $columns);

            if (!$warehouseZone) {
                return ApiResponse::error('Warehouse zone not found.', 404);
            }

            $warehouse = $warehouseZone->warehouse;

            if (Auth::id() !== $warehouse->vendor_id && Auth::role() !== 'admin') {
                return ApiResponse::error('Vendor can only review his warehouses zones location');
            }

            return ApiResponse::success($warehouseZone, 'Warehouse zone retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving warehouse zone: {$e->getMessage()}", [
                'zone_id' => $zoneId,
                'exception' => $e
            ]);

            return ApiResponse::error('An error occurred while retrieving the warehouse zone.', 500);
        }
    }

    public function search(string $warehouseId, string $query): JsonResponse
    {
        try {
            $warehouse = Warehouse::find($warehouseId);

            if (is_null($warehouse)) {
                return ApiResponse::error('The warehouse was not found.', 404);
            }

            if (Auth::id() !== $warehouse->vendor_id && Auth::role() !== 'admin') {
                return ApiResponse::error('Vendor can only review his warehouses zones location');
            }

            // Validate query input
            $validator = Validator::make(['query' => $query], [
                'query' => 'required|string|min:1',
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Invalid search query.', 422, $validator->errors());
            }

            $value = $validator->validated()['query'];

            $warehouseZones = WarehouseZone::where('warehouse_id', $warehouseId)
                ->where(function ($queryBuilder) use ($value) {
                    $queryBuilder->where('code', 'like', "%{$value}%")
                        ->orWhere('name', 'like', "%{$value}%")
                        ->orWhere('status', 'like', "%{$value}%");
                })
                ->get();

            if ($warehouseZones->isEmpty()) {
                return ApiResponse::error('No matching warehouse zones found.', 404);
            }

            return ApiResponse::success($warehouseZones, 'Warehouse zones found.');
        } catch (ModelNotFoundException $e) {
            Log::warning("Warehouse search failed: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error('Warehouse not found.', 404);
        } catch (Exception $e) {
            Log::error("Error searching warehouse zones: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error('An error occurred while searching for warehouse zones.', 500);
        }
    }

    public function location(Request $request, string $zoneId): JsonResponse
    {
        try {
            $warehouseZone = $this->warehouseZoneService->getById($zoneId);

            if (!$warehouseZone) {
                return ApiResponse::error('Warehouse zone not found.', 404);
            }

            $warehouse = Warehouse::with('city.country')->find($warehouseZone->warehouse_id);

            if (!$warehouse) {
                return ApiResponse::error('Warehouse not found.', 404);
            }

            if ($request->user()->id !== $warehouse->vendor_id && $request->user()->role !== 'admin') {
                return ApiResponse::error('Vendor can only review his warehouses zones location');
            }

            $location = "{$warehouse->name}" . " -> {$warehouse->city->name}" . " -> {$warehouse->city->country->name}" . " -> {$warehouse->city->country->continent->name}";

            return ApiResponse::success($location, 'warehouse zone location.');
        } catch (ModelNotFoundException $e) {
            Log::warning("warehouse zone search failed: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error('warehouse zone not found.', 404);
        } catch (Exception $e) {
            Log::error("Error searching warehouse zone: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error('An error occurred while searching for warehouse zone.', 500);
        }
    }

    public function store(StoreWarehouseZoneRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $warehouse = $this->warehouseZoneService->create($data);

            return ApiResponse::success($warehouse, 'Warehouse zone created successfully.');
        } catch (Exception $e) {
            Log::error("Error creating warehouse: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function update(UpdateWarehouseZoneRequest $request, string $id): JsonResponse
    {
        try {
            $warehouseZone = $this->warehouseZoneService->getById($id);

            if (!$warehouseZone) {
                return ApiResponse::error('Warehouse Zone not found.', 404);
            }

            if ($request->user()->id !== $warehouseZone->warehouse->vendor_id && $request->user()->role !== 'admin') {
                return ApiResponse::error('Vendor can only update his warehouses zones location');
            }

            $data = $request->validated();

            $warehouse = $this->warehouseZoneService->update($id, $data);

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
            $warehouseZone = $this->warehouseZoneService->getById($id);

            if (!$warehouseZone) {
                return ApiResponse::error('Warehouse Zone not found.', 404);
            }

            if ($request->user()->id !== $warehouseZone->warehouse->vendor_id && $request->user()->role !== 'admin') {
                return ApiResponse::error('Vendor can only delete his warehouses zones location');
            }

            $deleted = $this->warehouseZoneService->delete($id, true);

            return ApiResponse::success([], 'Warehouse  zone deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting warehouse zone: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}