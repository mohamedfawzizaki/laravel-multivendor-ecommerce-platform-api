<?php

namespace App\Http\Controllers\Api\Public\Products;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaginateRequest;
use Illuminate\Support\Facades\Validator;
use App\Services\Products\ProductDiscountService;

class ProductDiscountController extends Controller
{
    /**
     * Constructor to inject the ProductDiscountService dependency.
     *
     * @param ProductDiscountService $productDiscountService The service responsible for product-related operations.
     */
    public function __construct(protected ProductDiscountService $productDiscountService) {}

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
                ? $this->productDiscountService->getAllProductDiscounts(
                    perPage: $validated['per_page'] ?? 15, // Default to 15 if not specified.
                    columns: $columns,
                    pageName: $validated['pageName'] ?? 'page',
                    page: $validated['page'] ?? 1,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                )
                : $this->productDiscountService->getAllProductDiscounts(
                    columns: $columns,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                );

            return ApiResponse::success($products, 'Products discounts retrieved successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $product = $this->productDiscountService->getProductDiscountById($id);

            return ApiResponse::success($product, 'Product discount retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving product discount: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function search(string $productID): JsonResponse
    {
        try {
            $validator = Validator::make(['product_id' => $productID], [
                'product_id' => 'string|exists:products,id',
            ]);

            if ($validator->fails()) {
                Log::warning("Product discount retrieval validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }

            $product = DB::table('product_discounts')->where('product_id', $productID)->first();

            if (!$product) {
                return ApiResponse::error('Product discount not found.', 404);
            }

            return ApiResponse::success($product, 'Product discount retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving product discount: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function DiscountsOfSpecificProduct(string $productID): JsonResponse
    {
        try {
            $discounts = DB::table('product_discounts')->where('product_id', $productID)->get();

            return ApiResponse::success($discounts, 'Product discounts retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving product discounts : {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error("Error retrieving product discounts", 500);
        }
    }

    public function DiscountOfSpecificProduct(string $productID, string $discountID): JsonResponse
    {
        try {
            $discounts = DB::table('product_discounts')
                ->where('product_id', $productID)
                ->where('id', $discountID)
                ->get();

            return ApiResponse::success($discounts, 'Product discounts retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving product discounts : {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error("Error retrieving product discounts", 500);
        }
    }
}