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
use App\Services\Products\ProductDiscountService;
use App\Http\Requests\StoreProductDiscountRequest;
use App\Http\Requests\ValidateColumnAndConditionRequest;

class ProductDiscountController extends Controller
{
    /**
     * Constructor to inject the ProductDiscountService dependency.
     *
     * @param ProductDiscountService $productDiscountService The service responsible for product-related operations.
     */
    public function __construct(protected ProductDiscountService $productDiscountService, protected ProductService $productService) {}

    public function store(StoreProductDiscountRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $product = $this->productDiscountService->create($validated);

            return ApiResponse::success($product, 'Product discount created successfully.');
        } catch (Exception $e) {
            Log::error("Error creating product discount: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            // check if the vendor has this product:
            $productDiscount = $this->productDiscountService->getProductDiscountById($id);

            if (!$productDiscount) {
                return ApiResponse::error('Product Discount not found');
            }

            $product = $this->productService->getProductById($productDiscount->product_id);

            if ($request->user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only update his products');
            }

            $validator = Validator::make($request->all(), [
                'product_id'            => 'sometimes|exists:products,id',
                'discount_price'        => 'sometimes|numeric|min:0',        // Ensure it's a valid number ≥ 0
                'discount_percentage'   => 'sometimes|numeric|between:0,100', // Percentage must be between 0–100
                'start_date'            => 'sometimes|date|before_or_equal:end_date',
                'end_date'              => 'sometimes|date|after_or_equal:start_date',
            ]);

            if ($validator->fails()) {
                Log::warning("Product review updating validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }
            $validatedData = $validator->validated();

            $product = $this->productDiscountService->update($id, $validatedData);

            return ApiResponse::success($product, 'Product discount updated successfully.');
        } catch (Exception $e) {
            Log::error("Error updating product discount: {$e->getMessage()}", ['exception' => $e]);
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
                'product_id'            => 'sometimes|exists:products,id',
                'discount_price'        => 'sometimes|numeric|min:0',        // Ensure it's a valid number ≥ 0
                'discount_percentage'   => 'sometimes|numeric|between:0,100', // Percentage must be between 0–100
                'start_date'            => 'sometimes|date|before_or_equal:end_date',
                'end_date'              => 'sometimes|date|after_or_equal:start_date',

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

            $products = $this->productDiscountService->updateGroup($data, $conditions, $columns);

            return ApiResponse::success($products, 'Product updated successfully.');
        } catch (Exception $e) {
            Log::error("Error updating product: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function updateProductsDiscountsOfSpecificVendor(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id'            => 'sometimes|exists:products,id',
                'discount_price'        => 'sometimes|numeric|min:0',        // Ensure it's a valid number ≥ 0
                'discount_percentage'   => 'sometimes|numeric|between:0,100', // Percentage must be between 0–100
                'start_date'            => 'sometimes|date|before_or_equal:end_date',
                'end_date'              => 'sometimes|date|after_or_equal:start_date',

                'conditions'  => 'sometimes|array',
                'columns'     => 'sometimes|array',
            ]);

            if ($validator->fails()) {
                Log::warning("Products Discounts updating validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            };

            $validated = $validator->validated();

            $mainConditions = $validator->validated()['conditions'] ?? [];

            $columns = $validator->validated()['columns'] ?? ['*'];
            // Filter only valid product fields (excluding 'columns' , 'conditions')
            $data = array_filter($validated, function ($key) {
                return !in_array($key, ['columns', 'conditions']); // Exclude 'columns' and 'conditions' key and conditions key
            }, ARRAY_FILTER_USE_KEY);

            // check if the vendor has this product review:
            $products = $this->productService->getAllProducts(
                conditions: ["vendor_id:=:" . $request->user()->id]
            )->all();


            foreach ($products as $product) {
                $conditions = $mainConditions;
                $conditions[] = "product_id:=:{$product->id}";
                $productsDiscounts[] = $this->productDiscountService->updateGroup($data, $conditions, $columns);
            }

            return ApiResponse::success($productsDiscounts, 'Product Discounts updated successfully.');
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
                $productDiscount = $this->productDiscountService->getAllProductDiscounts(
                    withTrashed: true,
                    conditions: ["id:=:$id"]
                )->first();
            } else {
                $productDiscount = $this->productDiscountService->getProductDiscountById($id);
            }

            if (!$productDiscount) {
                return ApiResponse::error('Product discount not found');
            }

            $product = $this->productService->getProductById($productDiscount->product_id);

            if ($request->user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only delete his products discounts');
            }

            $product = $this->productDiscountService->delete($id, $forceDelete);

            return $forceDelete ?
                ApiResponse::success($product, 'Product discount permenantly deleted successfully.') :
                ApiResponse::success($product, 'Product discount soft deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting product discount: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function deleteAllProductDiscounts(ValidateColumnAndConditionRequest $request, string $productID)
    {
        try {
            $product = $this->productService->getProductById($productID);

            if (!$product) {
                return ApiResponse::error('Product not found');
            }
            
            if (Auth::user()->id !== $product?->vendor_id) {
                return ApiResponse::error('Vendor can only delete his products discounts');
            }

            $conditions = $request->validated()['conditions'] ?? [];
            $conditions[] = "product_id:=:{$productID}";

            $forceDelete = $request->validated()['force'] ?? false;

            $deletedProducts = $this->productDiscountService->deleteBulk($conditions, $forceDelete);

            return $forceDelete ?
                ApiResponse::success($deletedProducts, 'Products discounts permenantly deleted successfully.') :
                ApiResponse::success($deletedProducts, 'Products discounts soft deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting products discounts: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function deleteProductsDiscountsOfSpecificVendor(ValidateColumnAndConditionRequest $request): JsonResponse
    {
        try {
            $mainConditions = $request->validated()['conditions'] ?? [];

            $force = $request->validated()['force'] ?? false;

            // check if the vendor has this product review:
            $products = $this->productService->getAllProducts(
                conditions: ["vendor_id:=:" . $request->user()->id]
            )->all();


            foreach ($products as $product) {
                $conditions = $mainConditions;
                $conditions[] = "product_id:=:{$product->id}";
                $deletedProducts = $this->productDiscountService->deleteBulk($conditions, $force);
            }

            return $force ?
                ApiResponse::success($deletedProducts, 'Products discounts permenantly deleted successfully.') :
                ApiResponse::success($deletedProducts, 'Products discounts soft deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error updating product: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
    public function isSoftDeleted(string $id)
    {
        try {
            $isDeleted = $this->productDiscountService->softDeleted($id);

            return $isDeleted ?
                ApiResponse::success($isDeleted, 'Product discount is soft deleted') :
                ApiResponse::success($isDeleted, 'Product discount is not soft deleted');
        } catch (Exception $e) {
            Log::error("Error checking soft deleted product discount : {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function restore(Request $request, string $id)
    {
        try {
            // check if the vendor has this product discount:
            $productDiscount = $this->productDiscountService->getAllProductDiscounts(
                onlyTrashed: true,
                conditions: ["id:=:$id"]
            )->first();


            if (!$productDiscount) {
                return ApiResponse::error('Product discount not found');
            }

            $product = $this->productService->getProductById($productDiscount->product_id);

            if ($request->user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only restore his products discounts');
            }

            $productDiscount = $this->productDiscountService->restore($id);

            return ApiResponse::success($productDiscount, 'Product discount is restored');
        } catch (Exception $e) {
            Log::error("Error restoring soft deleted product discount: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function restoreAllProductDiscounts(ValidateColumnAndConditionRequest $request, string $productID)
    {
        try {
            $product = $this->productService->getProductById($productID);

            if (!$product) {
                return ApiResponse::error('Product not found');
            }
            
            if (Auth::user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only restore his product discounts');
            }

            $conditions = $request->validated()['conditions'] ?? [];
            $conditions[] = "product_id:=:{$productID}";

            $productDiscounts = $this->productDiscountService->restoreBulk($conditions);

            $restoredProductDiscounts = [];
            foreach ($productDiscounts as $productDiscount) {
                $restoredProductDiscounts[] = $this->productDiscountService->getProductDiscountById($productDiscount->id);
            }

            if (empty($restoredProductDiscounts)) {
                return ApiResponse::success([], 'No product discounts found to restore');
            }
            
            return ApiResponse::success($restoredProductDiscounts, 'Product discounts is restored');
        } catch (Exception $e) {
            Log::error("Error restoring product discounts: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function restoreProductsDiscountsOfSpecificVendor(ValidateColumnAndConditionRequest $request)
    {
        try {
            $mainConditions = $request->validated()['conditions'] ?? [];

            // check if the vendor has this product review:
            $products = $this->productService->getAllProducts(
                conditions: ["vendor_id:=:" . $request->user()->id]
            )->all();

            if (empty($products)) {
                return ApiResponse::success([], 'No Products for this vendor to restore their discounts');
            }
            
            $restoredProductDiscounts = [];
            foreach ($products as $product) {
                $conditions = $mainConditions;
                $conditions[] = "product_id:=:{$product->id}";
                $productsDiscounts[] = $this->productDiscountService->restoreBulk($conditions);
                foreach ($productsDiscounts as $productsDiscount) {
                    foreach ($productsDiscount as $productDiscount) {
                        $restoredProductDiscounts[] = $this->productDiscountService->getProductDiscountById($productDiscount->id);
                    }
                }
            }

            if (empty($restoredProductDiscounts)) {
                return ApiResponse::success($restoredProductDiscounts, 'No Product discounts to be restored');
            }
            
            return ApiResponse::success($restoredProductDiscounts, 'Product discounts is restored');
        } catch (Exception $e) {
            Log::error("Error restoring product discounts: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}