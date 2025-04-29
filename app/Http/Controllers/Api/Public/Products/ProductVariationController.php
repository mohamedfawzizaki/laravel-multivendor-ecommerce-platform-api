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
use App\Models\Products\ProductVariation;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ValidateColumnAndConditionRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductVariationController extends Controller
{
    public function index(PaginateRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $paginate = $validated['paginate'] ?? false;
            $columns = $validated['columns'] ?? ['*'];

            $products = $paginate
                ? ProductVariation::paginate(
                    perPage: $validated['per_page'] ?? 15, // Default to 15 if not specified.
                    columns: $columns,
                    pageName: $validated['pageName'] ?? 'page',
                    page: $validated['page'] ?? 1,
                )
                : ProductVariation::get($columns);

            return ApiResponse::success($products, 'Product variations retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving product: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error("Error retrieving product variations");
        }
    }

    public function show(ValidateColumnAndConditionRequest $request, string $id): JsonResponse
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];

            $productVariation = ProductVariation::find($id, $columns);

            return ApiResponse::success($productVariation, 'Product variation retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving product variation: {$e->getMessage()}", ['exception' => $e]);
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

            $result = ProductVariation::whereAny([
                'product_id',
                'variant_name',
                'sku',
                'price',
                'compare_price',
                'attributes'
            ], 'like', '%' . $value . '%')->get();

            // Check if the result is empty
            if ($result->isEmpty()) {
                return ApiResponse::error('No matching product variations found.', 404);
            }

            return ApiResponse::success($result, 'product variations found.');
        } catch (ModelNotFoundException $e) {
            Log::warning("product variations search failed: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error('product variations not found.', 404);
        } catch (Exception $e) {
            Log::error("Error searching product variations: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error('An error occurred while searching for product variations.', 500);
        }
    }

    public function variationsOfSpecificProduct(ValidateColumnAndConditionRequest $request, string $productID): JsonResponse
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];

            $product = Product::find($productID);

            if (!$product) {
                return ApiResponse::error('Product not found', 404);
            }
            
            $productVariation = ProductVariation::find($product->id, $columns);

            if (!$productVariation) {
                return ApiResponse::error('Product variation not found', 404);
            }

            return ApiResponse::success($productVariation, 'Product variation retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving product variation: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function variationOfSpecificProduct(ValidateColumnAndConditionRequest $request, string $productID, string $variationID): JsonResponse
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];

            $product = Product::find($productID);

            if (!$product) {
                return ApiResponse::error('Product not found', 404);
            }
            
            $productVariation = ProductVariation::where('product_id', $product->id)
                ->where('id', $variationID)->first($columns);

            if (!$productVariation) {
                return ApiResponse::error('Product variation not found', 404);
            }

            return ApiResponse::success($productVariation, 'Product variation retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving product variation: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}