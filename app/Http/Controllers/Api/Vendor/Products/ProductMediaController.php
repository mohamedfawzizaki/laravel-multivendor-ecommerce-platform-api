<?php

namespace App\Http\Controllers\Api\Vendor\Products;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Products\ProductMedia;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Products\StoreProductMediaRequest;
use App\Http\Requests\ValidateColumnAndConditionRequest;
use App\Http\Requests\Products\UpdateProductMediaRequest;
use App\Models\Products\Product;
use App\Models\Products\ProductVariation;

class ProductMediaController extends Controller
{
    public function store(StoreProductMediaRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $product = Product::find($validated['product_id']);

            if (!$product) {
                return ApiResponse::error('Product nodddt found');
            }

            if ($request->user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only update his products');
            }

            $variation = isset($validated['variation_id']) ? ProductVariation::find($validated['variation_id']) : null;

            $file = $request->file('file');
            $type = $validated['type'];

            switch ($type) {
                case 'image':
                    $path = $this->storeFile($file, 'media/products/images');
                    break;
                case 'video':
                    $path = $this->storeFile($file, 'media/products/videos');
                    break;
                case 'document':
                    $path = $this->storeFile($file, 'media/products/documents');
                    break;
            }

            $data = [
                'product_id' => $validated['product_id'],
                'variation_id' => $variation ? $validated['variation_id'] : null,
                'type' => $validated['type'],
                'path' => $path,
                'is_default' => $validated['is_default'] ?? false,
                'sort_order' => $validated['sort_order'] ?? 0,
                'metadata' => $validated['metadata'] ?? [],
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ];

            $productMedia = ProductMedia::create($data);

            return ApiResponse::success($productMedia, 'Product Media created successfully.');
        } catch (Exception $e) {
            if (isset($data['path'])) {
                switch ($type) {
                    case 'image':
                        $oldMediaPath = $this->getPath($data['path'], 'media/products/images/');
                    case 'video':
                        $oldMediaPath = $this->getPath($data['path'], 'media/products/videos/');
                    case 'document':
                        $oldMediaPath = $this->getPath($data['path'], 'media/products/documents/');
                }
                $this->deleteFile($oldMediaPath);
            }
            Log::error("Error creating product: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function update(UpdateProductMediaRequest $request, string $id): JsonResponse
    {
        try {
            // check if the vendor has this product:
            $productMedia = DB::table('product_media')->find($id);

            if (!$productMedia) {
                return ApiResponse::error('Product media not found');
            }

            $product = DB::table('products')->find($productMedia->product_id);

            if ($request->user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only update his products');
            }

            $validated = $request->validated();

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $type = $validated['type'] ?? $productMedia->type;
                $oldMediaPath = null;

                switch ($type) {
                    case 'image':
                        $oldMediaPath = $this->getPath($productMedia->path, 'media/products/images');
                        $path = $this->storeFile($file, 'media/products/images');
                        break;
                    case 'video':
                        $path = $this->storeFile($file, 'media/products/videos');
                        $oldMediaPath = $this->getPath($productMedia->path, 'media/products/videos');
                        break;
                    case 'document':
                        $oldMediaPath = $this->getPath($productMedia->path, 'media/products/documents');
                        $path = $this->storeFile($file, 'media/products/documents');
                        break;
                }
                $this->deleteFile($oldMediaPath);

                $validated['path'] = $path;
                $validated['mime_type'] = $file->getMimeType();
                $validated['file_size'] = $file->getSize();
                unset($validated['file']);
            }

            //      // Handle default flag (only one default per product)
            // if (isset($data['is_default']) && $data['is_default']) {
            //     DB::table('product_media')
            //         ->where('product_id', $media->product_id)
            //         ->update(['is_default' => false]);
            // }

            DB::beginTransaction();
            $productMedia = DB::table('product_media')
                ->where('id', $id)
                ->update($validated);

            if ($productMedia > 0) {
                $productMedia = ProductMedia::find($id);
            }
            DB::commit();

            return ApiResponse::success($productMedia, 'Product media updated successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            if ($request->hasFile('file')) {
                switch ($type) {
                    case 'image':
                        $newMediaPath = $this->getPath($path, 'media/products/images');
                        break;
                    case 'video':
                        $newMediaPath = $this->getPath($path, 'media/products/videos');
                        break;
                    case 'document':
                        $newMediaPath = $this->getPath($path, 'media/products/documents');
                        break;
                }
                $this->deleteFile($newMediaPath);
            }
            Log::error("Error updating product media : {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function delete(ValidateColumnAndConditionRequest $request, string $id)
    {
        try {
            // check if the vendor has this product:
            $productMedia = DB::table('product_media')->find($id);

            if (!$productMedia) {
                return ApiResponse::error('Product media not found');
            }

            $product = DB::table('products')->find($productMedia->product_id);

            if ($request->user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only delete his products');
            }

            $type = $productMedia->type;
            $oldMediaPath = null;

            switch ($type) {
                case 'image':
                    $oldMediaPath = $this->getPath($productMedia->path, 'media/products/images');
                    break;
                case 'video':
                    $oldMediaPath = $this->getPath($productMedia->path, 'media/products/videos');
                    break;
                case 'document':
                    $oldMediaPath = $this->getPath($productMedia->path, 'media/products/documents');
                    break;
            }

            $productMedia = ProductMedia::where('id', $id)->delete();

            if ($productMedia) {
                $this->deleteFile($oldMediaPath);
            }
            return ApiResponse::success($productMedia, 'Product media deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting product: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    private function deleteAllProductMedias(ValidateColumnAndConditionRequest $request, string $productID)
    {
        try {
            $product = DB::table('products')->find($productID);

            if ($request->user()->id !== $product->vendor_id) {
                return ApiResponse::error('Vendor can only delete his products');
            }

            $productMedia = DB::table('product_media')
                ->where('product_id', $productID)
                ->get();

            if ($productMedia->isEmpty()) {
                return ApiResponse::error('Product media not found to be deleted');
            }

            foreach ($productMedia as $item) {
                $type = $item->type;
                $oldMediaPath = null;
    
                switch ($type) {
                    case 'image':
                        $oldMediaPath = $this->getPath($item->path, 'media/products/images');
                        break;
                    case 'video':
                        $oldMediaPath = $this->getPath($item->path, 'media/products/videos');
                        break;
                    case 'document':
                        $oldMediaPath = $this->getPath($item->path, 'media/products/documents');
                        break;
                }
                $deleted = ProductMedia::where('id', $item->id)->delete();
                
                if ($deleted) {
                    $this->deleteFile($oldMediaPath);
                }
            }
            return ApiResponse::success($deleted, 'All Product media deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting product: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    private function storeFile($file, $path)
    {
        $storedPath = $file->store($path, 'public');
        return Storage::url($storedPath);
    }

    private function getPath($url, $path)
    {
        // Extract file path from URL
        $relativePath = str_replace(Storage::url($path), '', $url);

        // Full path in storage
        $fullPath = $path . '/' . ltrim($relativePath, '/');

        // Check if the file exists before deleting
        if (Storage::disk('public')->exists($fullPath)) {
            return $fullPath;
        }

        return null;
    }

    private function deleteFile($path)
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
            return true;
        }

        return false;
    }
}