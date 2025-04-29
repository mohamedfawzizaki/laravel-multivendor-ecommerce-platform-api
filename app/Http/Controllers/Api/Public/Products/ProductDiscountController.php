<?php

namespace App\Http\Controllers\Api\Public\Products;

use Exception;
use App\Models\Products\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaginateRequest;
use App\Models\Products\ProductDiscount;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ValidateColumnAndConditionRequest;
use App\Models\Products\ProductVariation;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductDiscountController extends Controller
{
    public function index(PaginateRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $paginate = $validated['paginate'] ?? false;
            $columns = $validated['columns'] ?? ['*'];

            $products = $paginate
                ? ProductDiscount::paginate(
                    perPage: $validated['per_page'] ?? 15, // Default to 15 if not specified.
                    columns: $columns,
                    pageName: $validated['pageName'] ?? 'page',
                    page: $validated['page'] ?? 1,
                )
                : ProductDiscount::get($columns);

            return ApiResponse::success($products, 'Product discounts retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving product discounts: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error("Error retrieving product discount");
        }
    }

    public function show(ValidateColumnAndConditionRequest $request, string $id): JsonResponse
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];

            $productDiscount = ProductDiscount::find($id, $columns);

            return ApiResponse::success($productDiscount, 'Product variation retrieved successfully.');
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

            $result = ProductDiscount::whereAny([
                'product_id',
                'variation_id',
                'discount_price',
                'discount_percentage',
                'start_date',
                'end_date'
            ], 'like', '%' . $value . '%')->get();

            // Check if the result is empty
            if ($result->isEmpty()) {
                return ApiResponse::error('No matching product discount found.', 404);
            }

            return ApiResponse::success($result, 'product discount found.');
        } catch (ModelNotFoundException $e) {
            Log::warning("product discount search failed: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error('product discount not found.', 404);
        } catch (Exception $e) {
            Log::error("Error searching product discount: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error('An error occurred while searching for product discount.', 500);
        }
    }

    public function discountOfSpecificProduct(ValidateColumnAndConditionRequest $request, string $productID): JsonResponse
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];

            $product = Product::find($productID);

            if (!$product) {
                return ApiResponse::error('Product not found', 404);
            }

            $productDiscount = ProductDiscount::where('product_id', $product->id)->get($columns);

            if ($productDiscount->isEmpty()) {
                return ApiResponse::error('Product discount not found', 404);
            }

            return ApiResponse::success($productDiscount, 'Product discount retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving product discount: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    // public function discountOfSpecificProductVariation(ValidateColumnAndConditionRequest $request, string $variationID): JsonResponse
    // {
    //     try {
    //         $columns = $request->validated()['columns'] ?? ['*'];

    //         $productVariation = ProductVariation::find($variationID);

    //         if (!$productVariation) {
    //             return ApiResponse::error('Product not found', 404);
    //         }

    //         $productDiscount = ProductDiscount::where('variation_id', $productVariation->id)->get($columns);

    //         if ($productDiscount->isEmpty()) {
    //             return ApiResponse::error('Product discount not found', 404);
    //         }

    //         return ApiResponse::success($productDiscount, 'Product discount retrieved successfully.');
    //     } catch (Exception $e) {
    //         Log::error("Error retrieving product discount: {$e->getMessage()}", ['exception' => $e]);
    //         return ApiResponse::error($e->getMessage(), 500);
    //     }
    // }

    public function specificDiscountOfSpecificProduct(ValidateColumnAndConditionRequest $request, string $productID, string $discountID): JsonResponse
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];

            $product = Product::find($productID);

            if (!$product) {
                return ApiResponse::error('Product not found', 404);
            }

            $productDiscount = ProductDiscount::where('product_id', $product->id)
                ->where('id', $discountID)->first($columns);

            if (!$productDiscount) {
                return ApiResponse::error('Product discount not found', 404);
            }

            return ApiResponse::success($productDiscount, 'Product discount retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving product discount: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}