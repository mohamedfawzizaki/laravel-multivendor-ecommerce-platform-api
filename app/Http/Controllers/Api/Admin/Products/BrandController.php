<?php

namespace App\Http\Controllers\Api\Admin\Products;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\Products\BrandService;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Products\StoreBrandRequest;
use App\Http\Requests\ValidateColumnAndConditionRequest;


class BrandController extends Controller
{
    /**
     * Constructor to inject the BrandService dependency.
     *
     * @param BrandService $brandService The service responsible for brand-related operations.
     */
    public function __construct(protected BrandService $brandService) {}

    public function store(StoreBrandRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $data = [
                'name' => $validated['name'],
                'description' => $validated['description'],
                'slug' => $validated['slug'],
                'logo_url' => $request->hasFile('logo')
                ? $this->brandService->storeFile(
                    $request->file('logo'),
                    'Brands/logos'
                    )
                    : null,
                    'website_url' => $validated['website_url'],
                ];
                
                $brand = $this->brandService->create($data);

            return ApiResponse::success($brand, 'Brand created successfully.');
        } catch (Exception $e) {
            if (isset($data['logo_url'])) {
                $oldLogoPath = $this->brandService->getPath($data['logo_url'], 'brands/logos/');
                $this->brandService->deleteFile($oldLogoPath);
            }

            Log::error("Error creating brand: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), $e->getCode());
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|unique:brands,name|max:256',
                'description' => 'sometimes|string',
                'logo' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048', // 2MB max
                'website_url' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                Log::warning("Brand updating validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }

            $validatedData = $validator->validated();

            if ($request->hasFile('logo')) {
                $validatedData['logo_url'] = $this->brandService->storeFile(
                    $request->file('logo'),
                    'Brands/logos'
                );
            }

            // exclode the logo from the validated data
            unset($validatedData['logo']);

            $brand = $this->brandService->update($id, $validatedData);

            return ApiResponse::success($brand, 'Brand updated successfully.');
        } catch (Exception $e) {
            if (isset($validatedData['logo_url'])) {
                $oldLogoPath = $this->brandService->getPath($validatedData['logo_url'], 'brands/logos/');
                $this->brandService->deleteFile($oldLogoPath);
            }

            Log::error("Error updating brand: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function delete(ValidateColumnAndConditionRequest $request, string $id)
    {
        try {
            $forceDelete = $request->validated()['force'] ?? false;

            $brand = $this->brandService->delete($id, $forceDelete);

            return $forceDelete ?
                ApiResponse::success($brand, 'Brand permenantly deleted successfully.') :
                ApiResponse::success($brand, 'Brand soft deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting brand: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function isSoftDeleted(string $id)
    {
        try {
            $isDeleted = $this->brandService->softDeleted($id);

            return $isDeleted ?
                ApiResponse::success($isDeleted, 'Brand is soft deleted') :
                ApiResponse::success($isDeleted, 'Brand is not soft deleted');
        } catch (Exception $e) {
            Log::error("Error checking soft deleted brand: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function restore(string $id)
    {
        try {
            $brand = $this->brandService->restore($id);

            return ApiResponse::success($brand, 'Brand is restored');
        } catch (Exception $e) {
            Log::error("Error restoring soft deleted brand: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}