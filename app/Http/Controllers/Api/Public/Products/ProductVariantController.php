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
use App\Services\Products\ProductVariantService;

class ProductVariantController extends Controller
{
    /**
     * Constructor to inject the ProductVariantService dependency.
     *
     * @param ProductVariantService $productVariantService The service responsible for product-related operations.
     */
    public function __construct(protected ProductVariantService $productVariantService) {}

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
                ? $this->productVariantService->getAllProductVariants(
                    perPage: $validated['per_page'] ?? 15, // Default to 15 if not specified.
                    columns: $columns,
                    pageName: $validated['pageName'] ?? 'page',
                    page: $validated['page'] ?? 1,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                )
                : $this->productVariantService->getAllProductVariants(
                    columns: $columns,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                );

            return ApiResponse::success($products, 'Products variants retrieved successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $product = $this->productVariantService->getProductVariantById($id);

            return ApiResponse::success($product, 'Product variant retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving product variant: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function search(string $name): JsonResponse
    {
        try {
            $validator = Validator::make(['name' => $name], [
                'name' => 'string|exists:product_variants,variant_name',
            ], [
                'name' => 'the name is invalid or not found in the database'
            ]);

            if ($validator->fails()) {
                Log::warning("Product status retrieval validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }

            $product = DB::table('product_variants')->where('variant_name', $name)->first();

            if (!$product) {
                return ApiResponse::error('Product variant not found.', 404);
            }

            return ApiResponse::success($product, 'Product variant retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving product variant: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function VariantsOfSpecificProduct(string $productID): JsonResponse
    {
        try {
            $variants = DB::table('product_variants')->where('product_id', $productID)->get();

            return ApiResponse::success($variants, 'Product variants retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving product variants : {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error("Error retrieving product variants", 500);
        }
    }

    public function VariantOfSpecificProduct(string $productID, string $variantID): JsonResponse
    {
        try {
            $variants = DB::table('product_variants')
                ->where('product_id', $productID)
                ->where('id', $variantID)
                ->get();

            return ApiResponse::success($variants, 'Product variants retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving product variants : {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error("Error retrieving product variants", 500);
        }
    }
}