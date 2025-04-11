<?php

namespace App\Http\Controllers\Api\Public\Products;

use App\Http\Controllers\Controller;
use App\Services\Products\ProductService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\PaginateRequest;
use App\Http\Requests\ValidateColumnAndConditionRequest;

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

    public function search(string $name): JsonResponse
    {
        try {
            $validator = Validator::make(['name'=>$name], [
                'name' => 'sometimes|string',
            ]);

            if ($validator->fails()) {
                Log::warning("Product retrieval validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }

            $validated = $validator->validated();

            $product = $this->productService->searchBy('name', $validated['name']);

            return ApiResponse::success($product, 'Product retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving product: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}