<?php

namespace App\Http\Controllers\Api\Vendor\Products;

use Exception;
use Illuminate\Http\Request;
use App\Models\Products\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Products\ProductDiscount;
use App\Http\Requests\ValidateColumnAndConditionRequest;
use App\Http\Requests\Products\StoreProductDiscountRequest;
use App\Http\Requests\Products\UpdateProductDiscountRequest;

class ProductDiscountController extends Controller
{
    public function store(StoreProductDiscountRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            if ($request->has('product_id')) {
                $product = Product::find($validated['product_id']);
                if (!$product) {
                    return ApiResponse::error('Product not found');
                }
            } else {
                // check if the vendor has this product:
                $productVariation = DB::table('product_variations')->find($validated['variation_id']);

                if (!$productVariation) {
                    return ApiResponse::error('Product variation not found');
                }

                $product = DB::table('products')->find($productVariation->product_id);
            }


            if ($request->user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only update his products');
            }

            $productDiscount = ProductDiscount::create($validated);

            return ApiResponse::success($productDiscount, 'Product discount created successfully.');
        } catch (Exception $e) {
            Log::error("Error creating product discount: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function update(UpdateProductDiscountRequest $request, string $id): JsonResponse
    {
        try {
            // check if the vendor has this product:
            $productDiscount = DB::table('product_discounts')->find($id);

            if (!$productDiscount) {
                return ApiResponse::error('Product discount not found');
            }

            if ($productDiscount->product_id) {
                $product = DB::table('products')->find($productDiscount->product_id);
            } else {
                // check if the vendor has this product:
                $productVariation = DB::table('product_variations')->find($id);

                if (!$productVariation) {
                    return ApiResponse::error('Product variation not found');
                }

                $product = DB::table('products')->find($productVariation->product_id);
            }

            if ($request->user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only update his products');
            }

            $validated = $request->validated();

            $updated = ProductDiscount::where('id', $id)->update($validated);

            return $updated ? ApiResponse::success(ProductDiscount::find($id), 'Product discount updated successfully.') :
                ApiResponse::error("Error updating product discount");
        } catch (Exception $e) {
            Log::error("Error updating product discount: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function delete(ValidateColumnAndConditionRequest $request, string $id)
    {
        try {
            $forceDelete = $request->validated()['force'] ?? false;

            // check if the vendor has this product:
            $productDiscount = DB::table('product_discounts')->find($id);

            if (!$productDiscount) {
                return ApiResponse::error('Product discount not found');
            }

            if ($productDiscount->product_id) {
                $product = DB::table('products')->find($productDiscount->product_id);
            } else {
                // check if the vendor has this product:
                $productVariation = DB::table('product_variations')->find($id);

                if (!$productVariation) {
                    return ApiResponse::error('Product variation not found');
                }

                $product = DB::table('products')->find($productVariation->product_id);
            }

            if ($request->user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only delete his products');
            }

            if ($forceDelete) {
                $deletedDiscount = ProductDiscount::where('id', $id)->forceDelete();
                return ApiResponse::success($deletedDiscount, 'Product Discount permenantly deleted successfully.');
            }

            $deletedDiscount = ProductDiscount::where('id', $id)->delete();
            return ApiResponse::success($deletedDiscount, 'Product Discount soft deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting product discount: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function deleteAllProductDiscounts(ValidateColumnAndConditionRequest $request, string $id)
    {
        try {
            $forceDelete = true;

            // check if the vendor has this product:
            $product = DB::table('products')->find($id);

            if (!$product) {
                return ApiResponse::error('Product not found');
            } else {
                // check if the vendor has this product:
                $productVariation = DB::table('product_variations')->find($id);

                if (!$productVariation) {
                    return ApiResponse::error('Product variation not found');
                }

                $product = DB::table('products')->find($productVariation->product_id);
            }

            if ($request->user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only delete his products');
            }

            if ($forceDelete) {
                $deletedDiscount = $productVariation ? ProductDiscount::withTrashed()->where('variation_id', $id)->forceDelete() :
                    ProductDiscount::withTrashed()->where('product_id', $id)->forceDelete();
                return ApiResponse::success($deletedDiscount, 'Product Discounts permenantly deleted successfully.');
            }

            $deletedDiscount = $productVariation ? ProductDiscount::where('variation_id', $id)->delete() :
                ProductDiscount::where('product_id', $id)->delete();
            return ApiResponse::success($deletedDiscount, 'Product Discount soft deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting product discount: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function isSoftDeleted(string $id)
    {
        try {
            $isDeleted = ProductDiscount::onlyTrashed()->find($id);

            return isset($isDeleted) ?
                ApiResponse::success($isDeleted, 'Product Discount is soft deleted') :
                ApiResponse::success($isDeleted, 'Product Discount is not soft deleted');
        } catch (Exception $e) {
            Log::error("Error checking soft deleted product Discount: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function restore(Request $request, string $id)
    {
        try {
            // check if the vendor has this product:
            $productDiscount = DB::table('product_discounts')->find($id);

            if (!$productDiscount) {
                return ApiResponse::error('Product discount not found');
            }

            if ($productDiscount->product_id) {
                $product = DB::table('products')->find($productDiscount->product_id);
            } else {
                // check if the vendor has this product:
                $productDiscount = DB::table('product_variations')->find($id);

                if (!$productDiscount) {
                    return ApiResponse::error('Product variation not found');
                }

                $product = DB::table('products')->find($productDiscount->product_id);
            }

            if ($request->user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only restore his products');
            }

            $productDiscount = ProductDiscount::where('id', $id)->restore($id);

            return ApiResponse::success($productDiscount, 'Product discount is restored');
        } catch (Exception $e) {
            Log::error("Error restoring soft deleted product discount: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}