<?php

namespace App\Http\Controllers\Api\Public\Products;

use Exception;
use App\Models\Products\Brand;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaginateRequest;
use App\Services\Products\BrandService;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ValidateColumnAndConditionRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BrandController extends Controller
{
    /**
     * Constructor to inject the BrandService dependency.
     *
     * @param BrandService $brandService The service responsible for brand-related operations.
     */
    public function __construct(protected BrandService $brandService) {}

    public function index(PaginateRequest $request): JsonResponse
    {

        try {
            $validated = $request->validated();
            $paginate = $validated['paginate'] ?? false;
            $withTrashed = $validated['with_trashed'] ?? false;
            $onlyTrashed = $validated['only_trashed'] ?? false;
            $conditions = $validated['conditions'] ?? [];
            $columns = $validated['columns'] ?? ['*'];

            $brands = $paginate
                ? $this->brandService->getAllBrands(
                    perPage: $validated['per_page'] ?? 15, // Default to 15 if not specified.
                    columns: $columns,
                    pageName: $validated['pageName'] ?? 'page',
                    page: $validated['page'] ?? 1,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                )
                : $this->brandService->getAllBrands(
                    columns: $columns,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                );

            return ApiResponse::success($brands, 'Brands retrieved successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function show(ValidateColumnAndConditionRequest $request, string $id): JsonResponse
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];

            $brand = $this->brandService->getBrandById($id, $columns);

            return ApiResponse::success($brand, 'Brand retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving brand: {$e->getMessage()}", ['exception' => $e]);
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

            $result = Brand::whereAny([
                'name',
                'slug',
                'description',
                'website_url',
            ], 'like', '%' . $value . '%')->get();

            // Check if the result is empty
            if ($result->isEmpty()) {
                return ApiResponse::error('No matching brands found.', 404);
            }

            return ApiResponse::success($result, 'brands found.');
        } catch (ModelNotFoundException $e) {
            Log::warning("brands search failed: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error('brands not found.', 404);
        } catch (Exception $e) {
            Log::error("Error searching brands: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error('An error occurred while searching for brands.', 500);
        }
    }
}