<?php

namespace App\Http\Controllers\Api\Public\Products;

use Exception;
use App\Models\Products\Category;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaginateRequest;
use Illuminate\Support\Facades\Validator;
use App\Services\Products\CategoryService;
use App\Http\Requests\ValidateColumnAndConditionRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CategoryController extends Controller
{
    /**
     * Constructor to inject the CategoryService dependency.
     *
     * @param CategoryService $categoryService The service responsible for category-related operations.
     */
    public function __construct(protected CategoryService $categoryService) {}

    public function index(PaginateRequest $request): JsonResponse
    {

        try {
            $validated = $request->validated();
            $paginate = $validated['paginate'] ?? false;
            $withTrashed = $validated['with_trashed'] ?? false;
            $onlyTrashed = $validated['only_trashed'] ?? false;
            $conditions = $validated['conditions'] ?? [];
            $columns = $validated['columns'] ?? ['*'];

            $categorys = $paginate
                ? $this->categoryService->getAllCategorys(
                    perPage: $validated['per_page'] ?? 15, // Default to 15 if not specified.
                    columns: $columns,
                    pageName: $validated['pageName'] ?? 'page',
                    page: $validated['page'] ?? 1,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                )
                : $this->categoryService->getAllCategorys(
                    columns: $columns,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                );

            return ApiResponse::success($categorys, 'Categorys retrieved successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function show(ValidateColumnAndConditionRequest $request, string $id): JsonResponse
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];

            $category = $this->categoryService->getCategoryById($id, $columns);

            return ApiResponse::success($category, 'Category retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving category: {$e->getMessage()}", ['exception' => $e]);
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

            $result = Category::whereAny([
                'name',
                'slug',
                'description',
            ], 'like', '%' . $value . '%')->get();

            // Check if the result is empty
            if ($result->isEmpty()) {
                return ApiResponse::error('No matching categories found.', 404);
            }

            return ApiResponse::success($result, 'categories found.');
        } catch (ModelNotFoundException $e) {
            Log::warning("categories search failed: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error('categories not found.', 404);
        } catch (Exception $e) {
            Log::error("Error searching categories: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error('An error occurred while searching for categories.', 500);
        }
    }
}