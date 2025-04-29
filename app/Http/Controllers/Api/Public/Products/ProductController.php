<?php

namespace App\Http\Controllers\Api\Public\Products;

use Exception;
use Illuminate\Http\Request;
use App\Models\Products\Product;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaginateRequest;
use App\Services\Products\ProductService;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ValidateColumnAndConditionRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductController extends Controller
{
    /**
     * Constructor to inject the ProductService dependency.
     *
     * @param ProductService $productService The service responsible for product-related operations.
     */
    public function __construct(protected ProductService $productService) {}

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
                ? $this->productService->getAllProducts(
                    perPage: $validated['per_page'] ?? 15, // Default to 15 if not specified.
                    columns: $columns,
                    pageName: $validated['pageName'] ?? 'page',
                    page: $validated['page'] ?? 1,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                )
                : $this->productService->getAllProducts(
                    columns: $columns,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                );

            return ApiResponse::success($products, 'Products retrieved successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function show(ValidateColumnAndConditionRequest $request, string $id): JsonResponse
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];

            $product = $this->productService->getProductById($id, $columns);

            return ApiResponse::success($product, 'Product retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving product: {$e->getMessage()}", ['exception' => $e]);
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

            $result = Product::whereAny([
                'name',
                'slug',
                'description',
                'base_price',
                'base_compare_price',
                'status',
            ], 'like', '%' . $value . '%')->get();

            // Check if the result is empty
            if ($result->isEmpty()) {
                return ApiResponse::error('No matching products found.', 404);
            }

            return ApiResponse::success($result, 'products found.');
        } catch (ModelNotFoundException $e) {
            Log::warning("products search failed: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error('products not found.', 404);
        } catch (Exception $e) {
            Log::error("Error searching products: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error('An error occurred while searching for products.', 500);
        }
    }
}