<?php

namespace App\Http\Controllers\Api\Public\Products;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaginateRequest;
use App\Services\Products\ImageService;

class ProductImageController extends Controller
{
    /**
     * Constructor to inject the ImageService dependency.
     *
     * @param ImageService $imageService The service responsible for product-related operations.
     */
    public function __construct(protected ImageService $imageService) {}

    public function index(PaginateRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $paginate = $validated['paginate'] ?? false;
            $withTrashed = $validated['with_trashed'] ?? false;
            $onlyTrashed = $validated['only_trashed'] ?? false;
            $conditions = $validated['conditions'] ?? [];
            $columns = $validated['columns'] ?? ['*'];

            $products = $paginate
                ? $this->imageService->getAllImages(
                    perPage: $validated['per_page'] ?? 15, // Default to 15 if not specified.
                    columns: $columns,
                    pageName: $validated['pageName'] ?? 'page',
                    page: $validated['page'] ?? 1,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                )
                : $this->imageService->getAllImages(
                    columns: $columns,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                );

            return ApiResponse::success($products, 'Products images retrieved successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $product = $this->imageService->getImageById($id);

            return ApiResponse::success($product, 'Product image retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving product image: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function ImagesOfSpecificProduct(string $productID): JsonResponse
    {
        try {
            $images = DB::table('product_images')->where('product_id', $productID)->get();

            return ApiResponse::success($images, 'Product images retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving product images : {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error("Error retrieving product images", 500);
        }
    }

    public function ImageOfSpecificProduct(string $productID, string $imageID): JsonResponse
    {
        try {
            $images = DB::table('product_images')
                ->where('product_id', $productID)
                ->where('id', $imageID)
                ->get();

            return ApiResponse::success($images, 'Product images retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving product images : {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error("Error retrieving product images", 500);
        }
    }
}