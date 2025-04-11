<?php

namespace App\Http\Controllers\Api\Public\Products;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaginateRequest;
use Illuminate\Support\Facades\Validator;
use App\Services\Products\ProductReviewService;
use App\Http\Requests\StoreProductReviewRequest;
use App\Http\Requests\ValidateColumnAndConditionRequest;

class ProductReviewController extends Controller
{
    /**
     * Constructor to inject the ProductReviewService dependency.
     *
     * @param ProductReviewService $productReviewService The service responsible for product-related operations.
     */
    public function __construct(protected ProductReviewService $productReviewService) {}

    public function index(PaginateRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $paginate = $validated['paginate'] ?? false;
            $withTrashed = $validated['with_trashed'] ?? false;
            $onlyTrashed = $validated['only_trashed'] ?? false;
            $conditions = $validated['conditions'] ?? [];
            $columns = $validated['columns'] ?? ['*'];

            $products = $paginate
                ? $this->productReviewService->getAllProductReviews(
                    perPage: $validated['per_page'] ?? 15, // Default to 15 if not specified.
                    columns: $columns,
                    pageName: $validated['pageName'] ?? 'page',
                    page: $validated['page'] ?? 1,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                )
                : $this->productReviewService->getAllProductReviews(
                    columns: $columns,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                );

            if ($products->isEmpty()) {
                return ApiResponse::success($products, 'No Products reviews to be retrieved.');
            }
            return ApiResponse::success($products, 'Products reviews retrieved successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $product = $this->productReviewService->getProductReviewById($id);

            return ApiResponse::success($product, 'Product review retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving product review: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    // public function search(string $name): JsonResponse
    // {}

    public function ReviewsOfSpecificProduct(string $productID): JsonResponse
    {
        try {
            $reviews = DB::table('product_reviews')->where('product_id', $productID)->get();

            return ApiResponse::success($reviews, 'Product reviews retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving product reviews : {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error("Error retrieving product reviews", 500);
        }
    }

    public function ReviewOfSpecificProduct(string $productID, string $reviewID): JsonResponse
    {
        try {
            $reviews = DB::table('product_reviews')
                ->where('product_id', $productID)
                ->where('id', $reviewID)
                ->get();

            return ApiResponse::success($reviews, 'Product reviews retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving product reviews : {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error("Error retrieving product reviews", 500);
        }
    }

    public function store(StoreProductReviewRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $product = $this->productReviewService->create($validated);

            return ApiResponse::success($product, 'Product review created successfully.');
        } catch (Exception $e) {
            Log::error("Error creating product review: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            // check if the vendor has this product:
            $productReview = $this->productReviewService->getProductReviewById($id);

            if (!$productReview) {
                return ApiResponse::error('Product Review not found');
            }

            if ($request->user()->id !== $productReview->user_id) {
                return ApiResponse::error('User can only update his product review');
            }

            $validator = Validator::make($request->all(), [
                'review'            => 'sometimes|string|min:5',
                'rating'            => 'sometimes|integer|between:1,5',
                // 'verified_purchase' => 'sometimes|boolean',
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

            return ApiResponse::success($productReview, 'Product review rupdated successfully.');
        } catch (Exception $e) {
            Log::error("Error updating product review: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function delete(ValidateColumnAndConditionRequest $request, string $id)
    {
        try {
            // check if the vendor has this product:
            $productReview = $this->productReviewService->getProductReviewById($id);

            if (!$productReview) {
                return ApiResponse::error('Product Review not found', 404);
            }

            if ($request->user()->id !== $productReview->user_id) {
                return ApiResponse::error('User can only delete his product review');
            }

            $product = $this->productReviewService->delete($id, false);

            return ApiResponse::success($product, 'Product review soft deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting product review: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}