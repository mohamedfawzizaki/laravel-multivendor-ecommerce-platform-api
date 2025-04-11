<?php

namespace App\Http\Controllers\Api\Vendor\Products;

use App\Http\Controllers\Controller;
use App\Services\Products\ProductService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\ValidateColumnAndConditionRequest;

class ProductController extends Controller
{
    /**
     * Constructor to inject the ProductService dependency.
     *
     * @param ProductService $productService The service responsible for product-related operations.
     */
    public function __construct(protected ProductService $productService) {}

    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['vendor_id'] = $request->user()->id;

            $product = $this->productService->create($validated);

            return ApiResponse::success($product, 'Product created successfully.');
        } catch (Exception $e) {
            Log::error("Error creating product: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            // check if the vendor has this product:
            $product = $this->productService->getProductById($id);

            if (!$product) {
                return ApiResponse::error('Product not found');
            }

            if ($request->user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only update his products');
            }

            $validator = Validator::make($request->all(), [
                'brand_id' => 'sometimes|string|exists:brands,id',
                'category_id' => 'sometimes|string|exists:categories,id',
                'status_id' => 'sometimes|string|exists:product_statuses,id',

                'name' => 'sometimes|string|max:256|unique:brands,name',
                // 'slug'=>'sometimes|string|max:256',
                'description' => 'sometimes|string',
            ]);

            if ($validator->fails()) {
                Log::warning("Product updating validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }
            $validatedData = $validator->validated();

            $product = $this->productService->update($id, $validatedData);

            return ApiResponse::success($product, 'Product updated successfully.');
        } catch (Exception $e) {
            Log::error("Error updating product: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function updateBulk(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'brand_id' => 'sometimes|string|exists:brands,id',
                'category_id' => 'sometimes|string|exists:categories,id',
                'status_id' => 'sometimes|string|exists:product_statuses,id',
                'description' => 'sometimes|string',

                'conditions'  => 'sometimes|array',
                'columns'     => 'sometimes|array',
            ]);

            if ($validator->fails()) {
                Log::warning("Products updating validation failed.", [
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

            $conditions[] = "vendor_id:=:{$request->user()->id}";

            $columns = $validator->validated()['columns'] ?? ['*'];

            // Filter only valid product fields (excluding 'columns' , 'conditions')
            $data = array_filter($validated, function ($key) {
                return !in_array($key, ['columns', 'conditions']); // Exclude 'columns' and 'conditions' key and conditions key
            }, ARRAY_FILTER_USE_KEY);

            $products = $this->productService->updateGroup($data, $conditions, $columns);

            return ApiResponse::success($products, 'Product updated successfully.');
        } catch (Exception $e) {
            Log::error("Error updating product: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function delete(ValidateColumnAndConditionRequest $request, string $id)
    {
        try {
            // check if the vendor has this product:
            $product = $this->productService->getProductById($id);

            if (!$product) {
                return ApiResponse::error('Product not found');
            }

            if ($request->user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only delete his products');
            }

            $forceDelete = $request->validated()['force'] ?? false;

            $product = $this->productService->delete($id, $forceDelete);

            return $forceDelete ?
                ApiResponse::success($product, 'Product permenantly deleted successfully.') :
                ApiResponse::success($product, 'Product soft deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting product: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function deleteBulk(ValidateColumnAndConditionRequest $request)
    {
        try {
            $conditions = $request->validated()['conditions'] ?? [];
            $conditions[] = "vendor_id:=:{$request->user()->id}";
            
            $forceDelete = $request->validated()['force'] ?? false;

            $deletedProducts = $this->productService->deleteBulk($conditions, $forceDelete);

            return $forceDelete ?
                ApiResponse::success($deletedProducts, 'Products permenantly deleted successfully.') :
                ApiResponse::success($deletedProducts, 'Products soft deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting products: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function isSoftDeleted(string $id)
    {
        try {
            $isDeleted = $this->productService->softDeleted($id);

            return $isDeleted ?
                ApiResponse::success($isDeleted, 'Product is soft deleted') :
                ApiResponse::success($isDeleted, 'Product is not soft deleted');
        } catch (Exception $e) {
            Log::error("Error checking soft deleted product: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function restore(Request $request, string $id)
    {
        try {
            // check if the vendor has this product:
            $product = $this->productService->getAllProducts(
                onlyTrashed:true,
                conditions:["id:=:$id"]
            )->first();
            
            if (!$product) {
                return ApiResponse::error('Product not found');
            }

            if ($request->user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only delete his products');
            }

            $product = $this->productService->restore($id);

            return ApiResponse::success($product, 'Product is restored');
        } catch (Exception $e) {
            Log::error("Error restoring soft deleted product: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function restoreBulk(ValidateColumnAndConditionRequest $request)
    {
        try {
            $conditions = $request->validated()['conditions'] ?? [];
            $conditions[] = "vendor_id:=:{$request->user()->id}";

            $products = $this->productService->restoreBulk($conditions);

            return ApiResponse::success($products, 'Products is restored');
        } catch (Exception $e) {
            Log::error("Error restoring products: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}