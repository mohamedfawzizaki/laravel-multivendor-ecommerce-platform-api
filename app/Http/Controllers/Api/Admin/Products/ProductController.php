<?php

namespace App\Http\Controllers\Api\Admin\Products;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\Products\ProductService;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Constructor to inject the ProductService dependency.
     *
     * @param ProductService $productService The service responsible for product-related operations.
     */
    public function __construct(protected ProductService $productService) {}

    public function pendding(Request $request): JsonResponse
    {
        return ApiResponse::success([]);
    }

    public function approve(Request $request): JsonResponse
    {
        return ApiResponse::success([]);
    }

    public function reject(Request $request): JsonResponse
    {
        return ApiResponse::success([]);
    }

    public function changeVendor(string $oldVendorId, string $newVendorId): JsonResponse
    {
        try {
            $validator = Validator::make([
                'old_vendor_id' => $oldVendorId,
                'new_vendor_id' => $newVendorId,
            ], [
                'old_vendor_id' => 'string|exists:users,id',
                'new_vendor_id' => 'string|exists:users,id',
            ]);

            if ($validator->fails()) {
                Log::warning("Vendor Exchange validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            };

            // check if the provided ids are vendors
            $oldVendor = User::whereId($oldVendorId)->first();
            if (!$oldVendor?->role->name == 'seller') {
                return ApiResponse::error('Old vendor is not a seller.', 400);
            }

            $newVendor = User::whereId($newVendorId)->first();
            if (!$newVendor?->role->name == 'seller') {
                return ApiResponse::error('New vendor is not a seller.', 400);
            }

            // check if the old vendor has any products

            $conditions[] = "vendor_id:=:{$oldVendorId}";

            $products = $this->productService->updateGroup([
                'vendor_id' => $newVendorId
            ], $conditions);

            return ApiResponse::success($products, 'Products Vendor updated successfully.');
        } catch (Exception $e) {
            Log::error("Error updating Products Vendor: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}