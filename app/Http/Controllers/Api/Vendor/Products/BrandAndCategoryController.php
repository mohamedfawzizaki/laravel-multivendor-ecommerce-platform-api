<?php

namespace App\Http\Controllers\Api\Vendor\Products;

use Exception;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\Products\BrandService;
use App\Models\Products\CategoryHierarchy;
use App\Services\Products\CategoryService;
use App\Http\Requests\Products\StoreBrandRequest;
use App\Http\Requests\Products\StoreCategoryRequest;


class BrandAndCategoryController extends Controller
{
    public function registerBrand(StoreBrandRequest $request, BrandService $brandService): JsonResponse 
    {
        try {
            $validated = $request->validated();

            $brand = $brandService->create($validated);

            return ApiResponse::success($brand, 'Brand created successfully.');
        } catch (Exception $e) {
            Log::error("Error creating brand: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function registerCategory(StoreCategoryRequest $request, CategoryService $categoryService, ?string $parentId = null): JsonResponse 
    {
        try {
            $validated = $request->validated();

            $category = $categoryService->create($validated);
            
            if ($parentId) {
                $categoryHierarchy = CategoryHierarchy::find($parentId);
                if (!$categoryHierarchy) {
                    return ApiResponse::error('Parent category not found', 404);
                }
                $categoryHierarchy->create([
                    'parent_id'=>$parentId,
                    'child_id'=>$category->id,
                ]);
            }
            return ApiResponse::success($category, 'Category created successfully.');
        } catch (Exception $e) {
            Log::error("Error creating category: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}