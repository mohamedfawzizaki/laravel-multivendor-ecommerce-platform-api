<?php

namespace App\Http\Controllers\Api\Public\Products;

use Exception;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaginateRequest;
use Illuminate\Support\Facades\Validator;
use App\Services\Products\CategoryService;
use App\Http\Requests\ValidateColumnAndConditionRequest;

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

    public function search(string $categoryName): JsonResponse
    {
        try {
            $validator = Validator::make(['name' => $categoryName], [
                'name' => 'required|string|exists:categories,name',
            ], [
                'name' => 'the selected category is invalid or is not found'
            ]);

            if ($validator->fails()) {
                Log::warning("Category retrieval validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }

            $validated = $validator->validated();

            $category = $this->categoryService->searchBy('name', $validated['name']);

            return ApiResponse::success($category, 'Category retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving category: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}