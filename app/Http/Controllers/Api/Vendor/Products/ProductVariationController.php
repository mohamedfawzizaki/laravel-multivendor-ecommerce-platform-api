<?php

namespace App\Http\Controllers\Api\Vendor\Products;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Products\ProductVariation;
use App\Http\Requests\ValidateColumnAndConditionRequest;
use App\Http\Requests\Products\StoreProductVariationRequest;
use App\Http\Requests\Products\UpdateProductVariationRequest;

class ProductVariationController extends Controller
{
    public function store(StoreProductVariationRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['vendor_id'] = $request->user()->id;

            $productVariation = ProductVariation::create([
                'product_id' => $validated['product_id'],
                'variant_name' => $validated['variant_name'],
                'sku' => $validated['sku'],
                'price' => $validated['price'],
                'compare_price' => $validated['compare_price'],
                'attributes' => $validated['attributes'],
            ]);

            return ApiResponse::success($productVariation, 'Product Variation created successfully.');
        } catch (Exception $e) {
            Log::error("Error creating product variation: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function update(UpdateProductVariationRequest $request, string $id): JsonResponse
    {
        try {
            // check if the vendor has this product:
            $productVariation = DB::table('product_variations')->find($id);

            if (!$productVariation) {
                return ApiResponse::error('Product variation not found');
            }

            $product = DB::table('products')->find($productVariation->product_id);

            if ($request->user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only update his products');
            }

            $validatedData = $request->validated();

            $productVariation = DB::table('product_variations')
            ->where('id', $id)
            ->update($validatedData);
            
            if ($productVariation > 0) {
                $productVariation = ProductVariation::find($id);
            }
            
            return ApiResponse::success($productVariation, 'Product variation updated successfully.');
        } catch (Exception $e) {
            Log::error("Error updating product variation : {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function delete(ValidateColumnAndConditionRequest $request, string $id)
    {
        try {
            // check if the vendor has this product:
            $productVariation = DB::table('product_variations')->find($id);

            if (!$productVariation) {
                return ApiResponse::error('Product variation not found');
            }

            $product = DB::table('products')->find($productVariation->product_id);

            if ($request->user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only delete his products');
            }

            $forceDelete = $request->validated()['force'] ?? false;

            if ($forceDelete) {
                $productVariation = ProductVariation::where('id', $id)->forceDelete();
                return ApiResponse::success($productVariation, 'Product permenantly deleted successfully.');
                
            }
            
            $productVariation = ProductVariation::where('id', $id)->delete();
            return ApiResponse::success($productVariation, 'Product soft deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting product: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function isSoftDeleted(string $id)
    {
        try {
            $isDeleted = ProductVariation::onlyTrashed()->find($id);

            return isset($isDeleted) ?
                ApiResponse::success($isDeleted, 'Product is soft deleted') :
                ApiResponse::success($isDeleted, 'Product is not soft deleted');
        } catch (Exception $e) {
            Log::error("Error checking soft deleted product: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function restore(Request $request, string $id)
    {
        try {
            // check if the vendor has this product:
            $productVariation = ProductVariation::onlyTrashed()->find($id);

            if (!$productVariation) {
                $productVariation = ProductVariation::find($id);
                if (!$productVariation) {
                    return ApiResponse::error('Product variation not found');
                }
            }

            $product = DB::table('products')->find($productVariation->product_id);

            if ($request->user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only delete his products');
            }

            $restored = $productVariation->restore();

            if ($restored) {
                return ApiResponse::success(ProductVariation::find($id), 'Product Variation is restored');
            }
            return ApiResponse::error('Product Variation is not restored');
        } catch (Exception $e) {
            Log::error("Error restoring soft deleted product Variation: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}