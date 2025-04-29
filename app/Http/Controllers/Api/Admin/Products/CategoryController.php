<?php

namespace App\Http\Controllers\Api\Admin\Products;

use Exception;
use Illuminate\Http\Request;
use App\Models\Products\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Products\CategoryHierarchy;
use App\Services\Products\CategoryService;
use App\Http\Requests\Products\StoreCategoryRequest;
use App\Http\Requests\ValidateColumnAndConditionRequest;

class CategoryController extends Controller
{
    /**
     * Constructor to inject the CategoryService dependency.
     *
     * @param CategoryService $categoryService The service responsible for category-related operations.
     */
    public function __construct(protected CategoryService $categoryService) {}

    public function store(StoreCategoryRequest $request, ?string $parentId = null): JsonResponse
    {
        DB::beginTransaction();
    
        try {
            $validated = $request->validated();
    
            // Create the category
            $category = $this->categoryService->create($validated);
    
            // If a parent ID is provided, create the hierarchy relation
            if ($parentId) {
                $parentCategory = Category::find($parentId);
    
                if (!$parentCategory) {
                    DB::rollBack(); // Rollback before returning error
                    return ApiResponse::error('Parent category not found', 404);
                }
    
                CategoryHierarchy::create([
                    'parent_id' => $parentId,
                    'child_id' => $category->id,
                ]);
            }
    
            DB::commit();
            return ApiResponse::success($category, 'Category created successfully.');
        } catch (Exception $e) {
            DB::rollBack();
    
            Log::error("Error creating category: {$e->getMessage()}", [
                'exception' => $e,
            ]);
    
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|unique:categories,name|max:256',
                'description' => 'sometimes|string',
            ]);

            if ($validator->fails()) {
                Log::warning("Category updating validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }
            
            $validatedData = $validator->validated();
            
            $category = $this->categoryService->update($id, $validatedData);

            return ApiResponse::success($category, 'Category updated successfully.');
        } catch (Exception $e) {
            Log::error("Error updating category: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function delete(ValidateColumnAndConditionRequest $request, string $id)
    {
        try {
            $forceDelete = $request->validated()['force'] ?? false;

            $category = $this->categoryService->delete($id, $forceDelete);

            return $forceDelete ?
                ApiResponse::success($category, 'Category permenantly deleted successfully.') :
                ApiResponse::success($category, 'Category soft deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting category: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function isSoftDeleted(string $id)
    {
        try {
            $isDeleted = $this->categoryService->softDeleted($id);

            return $isDeleted ?
                ApiResponse::success($isDeleted, 'Category is soft deleted') :
                ApiResponse::success($isDeleted, 'Category is not soft deleted');
        } catch (Exception $e) {
            Log::error("Error checking soft deleted category: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function restore(string $id)
    {
        try {
            $category = $this->categoryService->restore($id);

            return ApiResponse::success($category, 'Category is restored');
        } catch (Exception $e) {
            Log::error("Error restoring soft deleted category: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}