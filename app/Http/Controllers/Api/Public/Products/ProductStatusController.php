<?php

namespace App\Http\Controllers\Api\Public\Products;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;


class ProductStatusController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $products = DB::table('product_statuses')->get();

            return ApiResponse::success($products, 'Products retrieved successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $product = DB::table('product_statuses')->where('id', $id)->first();

            if (!$product) {
                return ApiResponse::error('Product not found.', 404);
            }

            return ApiResponse::success($product, 'Product retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving product: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function search(string $name): JsonResponse
    {
        try {
            $validator = Validator::make(['name' => $name], [
                'name' => 'string|exists:product_statuses,name',
            ], [
                'name'=>'the name is invalid or not found in the database'
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

            $product = DB::table('product_statuses')->where('name', $name)->first();

            if (!$product) {
                return ApiResponse::error('Product not found.', 404);
            }

            return ApiResponse::success($product, 'Product retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving product: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}