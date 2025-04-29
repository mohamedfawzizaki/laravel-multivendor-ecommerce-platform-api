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
use App\Models\Products\ProductMedia;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ValidateColumnAndConditionRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductMediaController extends Controller
{
    public function index(PaginateRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $paginate = $validated['paginate'] ?? false;
            $columns = $validated['columns'] ?? ['*'];

            $products = $paginate
                ? ProductMedia::paginate(
                    perPage: $validated['per_page'] ?? 15, // Default to 15 if not specified.
                    columns: $columns,
                    pageName: $validated['pageName'] ?? 'page',
                    page: $validated['page'] ?? 1,
                )
                : ProductMedia::get($columns);

            return ApiResponse::success($products, 'Product media retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving product: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error("Error retrieving product media");
        }
    }

    public function show(ValidateColumnAndConditionRequest $request, string $id): JsonResponse
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];

            $productMedia = ProductMedia::find($id, $columns);

            return ApiResponse::success($productMedia, 'Product variation retrieved successfully.');
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

            $result = ProductMedia::whereAny([
                'type',
                'mime_type',
                'file_size',
                'sort_order',
                'metadata',
            ], 'like', '%' . $value . '%')->get();

            // Check if the result is empty
            if ($result->isEmpty()) {
                return ApiResponse::error('No matching product media found.', 404);
            }

            return ApiResponse::success($result, 'product media found.');
        } catch (ModelNotFoundException $e) {
            Log::warning("product media search failed: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error('product media not found.', 404);
        } catch (Exception $e) {
            Log::error("Error searching product media: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error('An error occurred while searching for product media.', 500);
        }
    }

    public function mediaOfSpecificProduct(ValidateColumnAndConditionRequest $request, string $productID): JsonResponse
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];

            $product = Product::find($productID);

            if (!$product) {
                return ApiResponse::error('Product not found', 404);
            }

            $productMedia = ProductMedia::where('product_id', $product->id)->get($columns);

            if ($productMedia->isEmpty()) {
                return ApiResponse::error('Product media not found', 404);
            }

            return ApiResponse::success($productMedia, 'Product media retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving product media: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function specificMediaOfSpecificProduct(ValidateColumnAndConditionRequest $request, string $productID, string $mediaID): JsonResponse
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];

            $product = Product::find($productID);

            if (!$product) {
                return ApiResponse::error('Product not found', 404);
            }

            $productMedia = ProductMedia::where('product_id', $product->id)
                ->where('id', $mediaID)->first($columns);

            if (!$productMedia) {
                return ApiResponse::error('Product media not found', 404);
            }

            return ApiResponse::success($productMedia, 'Product media retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving product media: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}