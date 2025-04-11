<?php

namespace App\Http\Controllers\Api\Vendor\Products;

use Exception;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\Products\BrandService;
use App\Http\Requests\StoreBrandRequest;
use App\Services\Products\CategoryService;
use App\Http\Requests\StoreCategoryRequest;


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

    public function registerCategory(StoreCategoryRequest $request, CategoryService $categoryService): JsonResponse 
    {
        try {
            $validated = $request->validated();

            $category = $categoryService->create($validated);

            return ApiResponse::success($category, 'Category created successfully.');
        } catch (Exception $e) {
            Log::error("Error creating category: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}