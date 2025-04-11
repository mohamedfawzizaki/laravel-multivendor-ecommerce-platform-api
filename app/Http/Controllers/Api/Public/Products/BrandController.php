<?php

namespace App\Http\Controllers\Api\Public\Products;

use Exception;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaginateRequest;
use App\Services\Products\BrandService;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ValidateColumnAndConditionRequest;


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

    public function search(string $brandName): JsonResponse
    {
        try {
            // Validate input parameters.
            $validator = Validator::make(['name' => strtolower($brandName)], [
                'name' => 'required|string|exists:brands,name',
            ], [
                'name' => 'the selected brand is invalid or is not found'
            ]);

            if ($validator->fails()) {
                Log::warning("Brand retrieval validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }

            $validated = $validator->validated();
            
            $brand = $this->brandService->searchBy('name', $validated['name']);

            return ApiResponse::success($brand, 'Brand retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving brand: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}