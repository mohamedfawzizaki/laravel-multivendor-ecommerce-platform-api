<?php

namespace App\Http\Controllers\Api\Vendor\Products;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\Products\ProductService;
use Illuminate\Support\Facades\Validator;
use App\Services\Products\ProductVariantService;
use App\Http\Requests\StoreProductVariantRequest;
use App\Http\Requests\ValidateColumnAndConditionRequest;

class ProductVariantController extends Controller
{
    /**
     * Constructor to inject the ProductVariantService dependency.
     *
     * @param ProductVariantService $productVariantService The service responsible for product-related operations.
     */
    public function __construct(protected ProductVariantService $productVariantService, protected ProductService $productService) {}

    public function store(StoreProductVariantRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $product = $this->productVariantService->create($validated);

            return ApiResponse::success($product, 'Product variant created successfully.');
        } catch (Exception $e) {
            Log::error("Error creating product variant: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            // check if the vendor has this product:
            $productVariant = $this->productVariantService->getProductVariantById($id);

            if (!$productVariant) {
                return ApiResponse::error('Product Variant not found');
            }

            $product = $this->productService->getProductById($productVariant->product_id);

            if ($request->user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only update his products');
            }

            $validator = Validator::make($request->all(), [
                'product_id'    => 'sometimes|exists:products,id',
                'variant_name'  => 'sometimes|string|max:255',
                'price'         => 'sometimes|numeric|min:0',
                'stock'         => 'sometimes|integer|min:0',
                'sku'           => 'sometimes|string|max:100|unique:product_variants,sku',
                'attributes'    => 'sometimes|array',
            ]);

            if ($validator->fails()) {
                Log::warning("Product variant updating validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }
            $validatedData = $validator->validated();

            $product = $this->productVariantService->update($id, $validatedData);

            return ApiResponse::success($product, 'Product updated successfully.');
        } catch (Exception $e) {
            Log::error("Error updating product: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function updateBulk(Request $request, string $productID): JsonResponse
    {
        try {

            $product = $this->productService->getProductById($productID);

            if ($request->user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only update his products');
            }

            $validator = Validator::make($request->all(), [
                'variant_name'  => 'sometimes|string|max:255',
                'price'         => 'sometimes|numeric|min:0',
                'stock'         => 'sometimes|integer|min:0',
                'sku'           => 'sometimes|string|max:100|unique:product_variants,sku',
                'attributes'    => 'sometimes|array',

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

            $conditions[] = "product_id:=:{$productID}";

            $columns = $validator->validated()['columns'] ?? ['*'];

            // Filter only valid product fields (excluding 'columns' , 'conditions')
            $data = array_filter($validated, function ($key) {
                return !in_array($key, ['columns', 'conditions']); // Exclude 'columns' and 'conditions' key and conditions key
            }, ARRAY_FILTER_USE_KEY);

            $products = $this->productVariantService->updateGroup($data, $conditions, $columns);

            return ApiResponse::success($products, 'Product updated successfully.');
        } catch (Exception $e) {
            Log::error("Error updating product: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function delete(ValidateColumnAndConditionRequest $request, string $id)
    {
        try {
            $forceDelete = $request->validated()['force'] ?? false;

            if ($forceDelete) {
                // check if the vendor has this product discount:
                $productVariant = $this->productVariantService->getAllProductVariants(
                    withTrashed: true,
                    conditions: ["id:=:$id"]
                )->first();
            } else {
                $productVariant = $this->productVariantService->getProductVariantById($id);
            }
            

            if (!$productVariant) {
                return ApiResponse::error('Product variant not found');
            }

            $product = $this->productService->getProductById($productVariant->product_id);

            if ($request->user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only delete his products variants');
            }

            $product = $this->productVariantService->delete($id, $forceDelete);

            return $forceDelete ?
                ApiResponse::success($product, 'Product variant permenantly deleted successfully.') :
                ApiResponse::success($product, 'Product variant soft deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting product variant: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function deleteAllProductVariants(ValidateColumnAndConditionRequest $request, string $productID)
    {
        try {
            $product = $this->productService->getProductById($productID);

            if (!$product) {
                return ApiResponse::error('Product not found');
            }
            
            if (Auth::user()->id !== $product?->vendor_id) {
                return ApiResponse::error('Vendor can only delete his products');
            }

            $conditions = $request->validated()['conditions'] ?? [];
            $conditions[] = "product_id:=:{$productID}";

            $forceDelete = $request->validated()['force'] ?? false;

            $deletedProducts = $this->productVariantService->deleteBulk($conditions, $forceDelete);

            return $forceDelete ?
                ApiResponse::success($deletedProducts, 'Products variants permenantly deleted successfully.') :
                ApiResponse::success($deletedProducts, 'Products variants soft deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting products variants: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function isSoftDeleted(string $id)
    {
        try {
            $isDeleted = $this->productVariantService->softDeleted($id);

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
            // check if the vendor has this product variant:
            $productVariant = $this->productVariantService->getAllProductVariants(
                onlyTrashed: true,
                conditions: ["id:=:$id"]
            )->first();


            if (!$productVariant) {
                return ApiResponse::error('Product variant not found');
            }

            $product = $this->productService->getProductById($productVariant->product_id);

            if ($request->user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only restore his products variants');
            }

            $productVariant = $this->productVariantService->restore($id);

            return ApiResponse::success($productVariant, 'Product variant is restored');
        } catch (Exception $e) {
            Log::error("Error restoring soft deleted product variant: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function restoreAllProductVariants(ValidateColumnAndConditionRequest $request, string $productID)
    {
        try {
            $product = $this->productService->getProductById($productID);

            if (!$product) {
                return ApiResponse::error('Product not found');
            }
            
            if (Auth::user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only restore his product varaints');
            }

            $conditions = $request->validated()['conditions'] ?? [];
            $conditions[] = "product_id:=:{$productID}";

            $productVariants = $this->productVariantService->restoreBulk($conditions);
            // check if the vendor has this product variant:
            $productVariants = $this->productVariantService->getAllProductVariants(
                conditions: ["product_id:=:{$productID}"]
            )->all();

            if (empty($productVariants)) {
                return ApiResponse::success([], 'No product varaints found to restore');
            }

            return ApiResponse::success($productVariants, 'Product varaints is restored');
        } catch (Exception $e) {
            Log::error("Error restoring product varaints: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}