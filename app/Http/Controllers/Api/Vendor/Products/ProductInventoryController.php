<?php

namespace App\Http\Controllers\Api\Vendor\Products;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\PaginateRequest;
use Illuminate\Support\Facades\Validator;
use App\Services\Products\ProductInventoryService;
use App\Http\Requests\StoreProductInventoryRequest;
use App\Http\Requests\ValidateColumnAndConditionRequest;


class ProductInventoryController extends Controller
{

    /**
     * Constructor to inject the ProductInventoryService dependency.
     *
     * @param ProductInventoryService $productInventoryService The service responsible for productInventory-related operations.
     */
    public function __construct(protected ProductInventoryService $productInventoryService, protected \App\Services\Products\ProductService $productService) {}

    public function index(PaginateRequest $request): JsonResponse
    {

        try {
            $validated       = $request->validated();
            $paginate        = $validated['paginate'] ?? false;
            $columns         = $validated['columns'] ?? ['*'];
            $mainConditions  = $validated['conditions'] ?? [];

            $vendorId = $request->user()->id;

            // Get all products for this vendor
            $products = $this->productService->getAllProducts(
                conditions: ["vendor_id:=:$vendorId"]
            )->all();

            if (empty($products)) {
                return ApiResponse::success([], 'No products found for the vendor.');
            }

            $productInventories = collect();

            if ($paginate) {
                foreach ($products as $product) {
                    $conditions = array_merge($mainConditions, ["product_id:=:$product->id"]);
                    
                    $inventories = $this->productInventoryService->getAllProductInventories(
                        perPage: $validated['per_page'] ?? 15, // Default to 15 if not specified.
                        pageName: $validated['pageName'] ?? 'page',
                        page: $validated['page'] ?? 1,
                        columns: $columns,
                        conditions: $conditions
                        )->all();
                        
                        $productInventories = $productInventories->merge($inventories);
                    }
                } else {
                foreach ($products as $product) {
                    $conditions = array_merge($mainConditions, ["product_id:=:$product->id"]);
    
                    $inventories = $this->productInventoryService->getAllProductInventories(
                        columns: $columns,
                        conditions: $conditions
                    )->all();
    
                    $productInventories = $productInventories->merge($inventories);
                }
            }

            if ($productInventories->isEmpty()) {
                return ApiResponse::success([], 'No product inventories found for the vendor.');
            }

            // Return a success response with the retrieved productInventorys.
            return ApiResponse::success($productInventories, 'Product inventories retrieved successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function show(ValidateColumnAndConditionRequest $request, string $id): JsonResponse
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];

            $productInventory = $this->productInventoryService->getProductInventoryById($id, $columns);

            if (!$productInventory) {
                return ApiResponse::error('ProductInventory not found', 404);
            }

            return ApiResponse::success($productInventory, 'ProductInventory retrieved successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error retrieving productInventory: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function store(StoreProductInventoryRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            // check that product_id and warehouse_id are unique together
            $productInventory = DB::table('product_inventory')
                ->where('product_id', $data['product_id'])
                ->where('warehouse_id', $data['warehouse_id'])
                ->first();

            if ($productInventory) {
                return ApiResponse::error('Product and warehouse already present.', 400);
            }

            if ($data['quantity_in_stock'] > 0 && $data['last_restocked_at'] == null) {
                $data['last_restocked_at'] = Carbon::now();
            }

            $productInventory = $this->productInventoryService->create($data);

            return ApiResponse::success($productInventory, 'ProductInventory created successfully.');
        } catch (Exception $e) {
            Log::error("Error creating productInventory: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $productInventory = $this->productInventoryService->getProductInventoryById($id);

            if (!$productInventory) {
                return ApiResponse::error('ProductInventory not found.', 404);
            }
            // Validate input parameters.
            $validator = Validator::make($request->all(), [
                'warehouse_id'        => 'sometimes|exists:warehouses,id',
                'product_id'          => 'sometimes|exists:products,id',
                'quantity_in_stock'   => 'sometimes|integer|min:0',
                'restock_threshold'   => 'sometimes|integer|min:0',
                'last_restocked_at'   => 'sometimes|date',
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("ProductInventory updating validation failed.", [
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

            if (isset($data['warehouse_id']) || isset($data['warehouse_id'])) {
                // check that product_id and warehouse_id are unique together
                $productInventoryPresent = DB::table('product_inventory')
                    ->where('product_id', $data['product_id'])
                    ->where('warehouse_id', $data['warehouse_id'])
                    ->first();

                if ($productInventoryPresent) {
                    return ApiResponse::error('Product and warehouse shoud be unique.', 400);
                }
            }

            // Get all products for this vendor
            $product = $this->productService->getProductById($productInventory->product_id);

            if (Auth::user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only update his productInventorys');
            }

            $productInventory = $this->productInventoryService->update($id, $data);

            return ApiResponse::success($productInventory, 'ProductInventory updated successfully.');
        } catch (Exception $e) {
            Log::error("ProductInventory update system error", [
                'productInventory_id' => $id,
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
                'quantity_in_stock'   => 'sometimes|integer|min:0',
                'restock_threshold'   => 'sometimes|integer|min:0',
                'last_restocked_at'   => 'sometimes|date',

                'conditions'  => 'sometimes|array',
                'columns'     => 'sometimes|array',
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("ProductInventory updating validation failed.", [
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

            $products = $this->productService->getAllProducts(
                conditions: ["vendor_id:=:" . Auth::user()->id]
            )->all();

            $productInventories = collect();


            foreach ($products as $product) {
                $conditions = array_merge($mainConditions, ["product_id:=:$product->id"]);
                $updatedProductInventories = $this->productInventoryService
                    ->updateGroup($data, $conditions, $columns)
                    ->all();

                $productInventories = $productInventories->merge($updatedProductInventories);
            }

            if ($productInventories->isEmpty()) {
                return ApiResponse::success([], 'No product inventories found for the vendor.');
            }
            // Return success response.
            return ApiResponse::success($productInventories, 'ProductInventories updated successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error updating ProductInventories: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function delete(string $id): JsonResponse
    {
        try {
            $productInventory = $this->productInventoryService->getProductInventoryById($id);

            if (!$productInventory) {
                return ApiResponse::error('ProductInventory not found.', 404);
            }

            // Get all products for this vendor
            $product = $this->productService->getProductById($productInventory->product_id);

            if (Auth::user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only delete his productInventories');
            }

            $deleted = $this->productInventoryService->delete($id, true);

            return ApiResponse::success([], 'ProductInventory deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting productInventory: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function deleteBulk(ValidateColumnAndConditionRequest $request): JsonResponse
    {
        try {
            $mainConditions = $request->validated()['conditions'] ?? [];

            $products = $this->productService->getAllProducts(
                conditions: ["vendor_id:=:" . Auth::user()->id]
            )->all();

            $deletedProductInventories = 0;

            foreach ($products as $product) {
                $conditions = array_merge($mainConditions, ["product_id:=:$product->id"]);
                $deletedProductInventories += $this->productInventoryService->deleteBulk($conditions, true);
            }

            return ApiResponse::success($deletedProductInventories, 'ProductInventories deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting productInventorys: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function deleteProductFromSpecificWarehouse(string $warehouseID, string $productID): JsonResponse
    {
        try {
            // Validate input parameters.
            $validator = Validator::make([
                'warehouseID' => $warehouseID,
                'productID' => $productID
            ], [
                'warehouseID'        => 'exists:warehouses,id',
                'productID'          => 'exists:products,id',
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("ProductInventory updating validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }

            $deletedProductInventories = DB::table('product_inventory')
                ->where('product_id', $productID)
                ->where('warehouse_id', $warehouseID)->delete();

            return ApiResponse::success($deletedProductInventories, 'ProductInventories deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting productInventorys: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}