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
use App\Services\Products\ProductReviewService;
use App\Http\Requests\ValidateColumnAndConditionRequest;


class ProductReviewController extends Controller
{
    /**
     * Constructor to inject the ProductReviewService dependency.
     *
     * @param ProductReviewService $productReviewService The service responsible for product-related operations.
     */
    public function __construct(protected ProductReviewService $productReviewService, protected ProductService $productService) {}

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            // check if the vendor has this product review:
            $productReview = $this->productReviewService->getProductReviewById($id);

            if (!$productReview) {
                return ApiResponse::error('Product Review not found');
            }

            $product = $this->productService->getProductById($productReview->product_id);

            if ($request->user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only update his products reviews');
            }

            $validator = Validator::make($request->all(), [
                'review'            => 'sometimes|string|min:5',
                'rating'            => 'sometimes|integer|between:1,5',
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

            $productReview = $this->productReviewService->update($id, $validatedData);

            if (!$productReview) {
                return ApiResponse::success($productReview, 'Product review is not updated.');
            }
            return ApiResponse::success($productReview, 'Product review updated successfully.');
        } catch (Exception $e) {
            Log::error("Error updating product review: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
    public function updateBulk(Request $request, string $productID): JsonResponse
    {
        try {
            $product = $this->productService->getProductById($productID);

            if (!$product) {
                return ApiResponse::error('Product not found', 404);
            }

            if ($request->user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only update his products reviews');
            }

            $validator = Validator::make($request->all(), [
                'review'            => 'sometimes|string|min:5',
                'rating'            => 'sometimes|integer|between:1,5',

                'conditions'  => 'sometimes|array',
                'columns'     => 'sometimes|array',
            ]);

            if ($validator->fails()) {
                Log::warning("Products reviews updating validation failed.", [
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

            $productReviews = $this->productReviewService->updateGroup($data, $conditions, $columns);

            if ($productReviews->isEmpty()) {
                return ApiResponse::success($productReviews, 'No Product reviews are updated.');
            }
            return ApiResponse::success($productReviews, 'Product review updated successfully.');
        } catch (Exception $e) {
            Log::error("Error updating product review: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function delete(ValidateColumnAndConditionRequest $request, string $id)
    {
        try {
            $forceDelete = $request->validated()['force'] ?? false;

            if ($forceDelete) {
                // check if the vendor has this product discount:
                $productReview = $this->productReviewService->getAllProductReviews(
                    withTrashed: true,
                    conditions: ["id:=:$id"]
                )->first();
            } else {
                $productReview = $this->productReviewService->getProductReviewById($id);
            }

            if (!$productReview) {
                return ApiResponse::error('Product review not found');
            }

            $product = $this->productService->getProductById($productReview->product_id);

            if ($request->user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only delete his products reviews');
            }

            $product = $this->productReviewService->delete($id, $forceDelete);

            return $forceDelete ?
                ApiResponse::success($product, 'Product review permenantly deleted successfully.') :
                ApiResponse::success($product, 'Product review soft deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting product review: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function deleteAllProductReviews(ValidateColumnAndConditionRequest $request, string $productID)
    {
        try {
            $product = $this->productService->getProductById($productID);

            if (!$product) {
                return ApiResponse::error('Product not found');
            }
            
            if (Auth::user()->id !== $product?->vendor_id) {
                return ApiResponse::error('Vendor can only delete his products reviews');
            }

            $conditions = $request->validated()['conditions'] ?? [];
            $conditions[] = "product_id:=:{$productID}";

            $forceDelete = $request->validated()['force'] ?? false;

            $deletedProducts = $this->productReviewService->deleteBulk($conditions, $forceDelete);

            return $forceDelete ?
                ApiResponse::success($deletedProducts, 'Products reviews permenantly deleted successfully.') :
                ApiResponse::success($deletedProducts, 'Products reviews soft deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting products reviews: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function isSoftDeleted(string $id)
    {
        try {
            $isDeleted = $this->productReviewService->softDeleted($id);

            return $isDeleted ?
                ApiResponse::success($isDeleted, 'Product review is soft deleted') :
                ApiResponse::success($isDeleted, 'Product review is not soft deleted');
        } catch (Exception $e) {
            Log::error("Error checking soft deleted product review: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function restore(Request $request, string $id)
    {
        try {
            // check if the vendor has this product review:
            $productReview = $this->productReviewService->getAllProductReviews(
                onlyTrashed: true,
                conditions: ["id:=:$id"]
            )->first();


            if (!$productReview) {
                return ApiResponse::error('Product review not found');
            }

            $product = $this->productService->getProductById($productReview->product_id);

            if ($request->user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only restore his products reviews');
            }

            $productReview = $this->productReviewService->restore($id);

            return ApiResponse::success($productReview, 'Product review is restored');
        } catch (Exception $e) {
            Log::error("Error restoring soft deleted product review: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function restoreAllProductReviews(ValidateColumnAndConditionRequest $request, string $productID)
    {
        try {
            $product = $this->productService->getProductById($productID);

            if (!$product) {
                return ApiResponse::error('Product not found');
            }
            
            if (Auth::user()->id !== $product?->vendor_id) {
                return ApiResponse::error('Vendor can only restore his product reviews');
            }

            $conditions = $request->validated()['conditions'] ?? [];
            $conditions[] = "product_id:=:{$productID}";

            $productReviews = $this->productReviewService->restoreBulk($conditions);

            $restoredProductReviews = [];
            foreach ($productReviews as $productReview) {
                $restoredProductReviews[] = $this->productReviewService->getProductReviewById($productReview->id);
            }

            if (empty($restoredProductReviews)) {
                return ApiResponse::success([], 'No product reviews found to restore');
            }
            
            return ApiResponse::success($restoredProductReviews, 'Product reviews is restored');
        } catch (Exception $e) {
            Log::error("Error restoring product reviews: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}