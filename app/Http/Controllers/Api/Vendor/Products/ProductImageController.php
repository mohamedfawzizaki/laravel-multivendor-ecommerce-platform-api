<?php

namespace App\Http\Controllers\Api\Vendor\Products;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\Products\ImageService;
use App\Services\Products\ProductService;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreProductImageRequest;

class ProductImageController extends Controller
{
    /**
     * Constructor to inject the ImageService dependency.
     *
     * @param ImageService $imageService The service responsible for product-related operations.
     */
    public function __construct(protected ImageService $imageService, protected ProductService $productService) {}

    public function store(StoreProductImageRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $product = $this->productService->getProductById($validated['product_id']);

            if (!$product) {
                return ApiResponse::error('Product nodddt found');
            }

            if ($request->user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only update his products');
            }

            $data = [
                'product_id' => $validated['product_id'],
                'image_url' => $this->imageService->storeFile(
                    $request->file('image'),
                    'Products/Images'
                ),
                'is_primary' => $validated['is_primary'] ?? false,
            ];

            $product = $this->imageService->create($data);

            return ApiResponse::success($product, 'Product created successfully.');
        } catch (Exception $e) {
            if (isset($data['image_url'])) {
                $oldImagePath = $this->imageService->getPath($data['image_url'], 'Products/Images/');
                $this->imageService->deleteFile($oldImagePath);
            }
            Log::error("Error creating product: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            // check if the vendor has this product:
            $image = $this->imageService->getImageById($id);

            if (!$image) {
                return ApiResponse::error('Product image not found');
            }

            $product = $this->productService->getProductById($image->product_id);
            
            if ($request->user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only update his products');
            }

            $validator = Validator::make($request->all(), [
                'product_id' => 'sometimes|string|exists:products,id',
                'image' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048', // 2MB max
                'is_primary' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                Log::warning("Product image updating validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }

            $validatedData = $validator->validated();

            if ($request->hasFile('image')) {
                $validatedData['image_url'] = $this->imageService->storeFile(
                    $request->file('image'),
                    'products/images'
                );
            }

            // exclode the image from the validated data
            unset($validatedData['image']);

            $image = $this->imageService->update($id, $validatedData);

            return ApiResponse::success($image, 'Product image updated successfully.');
        } catch (Exception $e) {
            if (isset($validatedData['image_url'])) {
                $oldImagePath = $this->imageService->getPath($validatedData['image_url'], 'products/images/');
                $this->imageService->deleteFile($oldImagePath);
            }

            Log::error("Error updating product image: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }


    public function delete(Request $request, string $id)
    {
        try {
            $image = $this->imageService->getImageById($id);

            if (!$image) {
                return ApiResponse::error('Product image not found');
            }

            $product = $this->productService->getProductById($image->product_id);
            
            if ($request->user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only delete his products');
            }

            $deleted = $this->imageService->delete($id, true);

            return ApiResponse::success($image, 'Product permenantly deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting product: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function deleteAllProductImages(string $productID)
    {
        try {
            $product = $this->productService->getProductById($productID);
            
            if (Auth::user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only delete his products');
            }
            
            $conditions[] = "product_id:=:{$productID}";

            $deletedProducts = $this->imageService->deleteBulk($conditions, true);

            return ApiResponse::success($deletedProducts, 'Products images permenantly deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting products images: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}