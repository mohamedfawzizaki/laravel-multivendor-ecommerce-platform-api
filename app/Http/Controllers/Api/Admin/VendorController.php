<?php

namespace App\Http\Controllers\Api\Admin;

use Exception;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Services\VendorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaginateRequest;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreVendorDetailsRequest;
use App\Http\Requests\UpdateVendorDetailsRequest;
use App\Http\Requests\ValidateColumnAndConditionRequest;


class VendorController extends Controller
{
    
    /**
     * Constructor to inject the VendorService dependency.
     *
     * @param VendorService $vendorService The service responsible for vendor-related operations.
     */
    public function __construct(protected VendorService $vendorService) {}

    public function index(PaginateRequest $request): JsonResponse
    {

        try {
            // Extract validated input values with default fallbacks.
            $validated = $request->validated();
            $paginate = $validated['paginate'] ?? false;
            $withTrashed = $validated['with_trashed'] ?? false;
            $onlyTrashed = $validated['only_trashed'] ?? false;
            $conditions = $validated['conditions'] ?? [];
            $columns = $validated['columns'] ?? ['*'];


            // var_dump($conditions);
            // Retrieve vendors based on pagination preference.
            $vendors = $paginate
                ? $this->vendorService->getAllVendors(
                    perPage: $validated['per_page'] ?? 15, // Default to 15 if not specified.
                    columns: $columns,
                    pageName: $validated['pageName'] ?? 'page',
                    page: $validated['page'] ?? 1,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                )
                : $this->vendorService->getAllVendors(
                    columns: $columns,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                );

            // Return a success response with the retrieved vendors.
            return ApiResponse::success($vendors, 'Vendors retrieved successfully.');
        } catch (Exception $e) {
            // Handle any exceptions and return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function show(ValidateColumnAndConditionRequest $request, string $id): JsonResponse
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];

            // Validate the ID format (either UUID for vendor_id or user_id)
            if (Str::isUuid($id)) {
                $vendor =  $this->vendorService->searchBy('user_id', $id, $columns);
            } else {
                $vendor = $this->vendorService->getVendorById($id, $columns);
            }

            if (!$vendor) {
                return ApiResponse::error('Vendor not found', 404);
            }

            return ApiResponse::success($vendor, 'Vendor retrieved successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error retrieving vendor: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function searchBy(Request $request): JsonResponse
    {
        try {
            // Validate input parameters.
            $validator = Validator::make($request->all(), [
                'business_name' => 'required|string',
                'columns' => 'sometimes|array', // Optional columns parameter.
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("Vendor retrieval validation failed.", [
                    'errors' => $validator->errors(),
                    'input' => $request->all(), // Log the provided input for debugging.
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }

            // Extract validated data.
            $validated = $validator->validated();
            $columns = $validated['columns'] ?? ['*'];

            $vendor = $this->vendorService->searchBy('business_name', $validated['business_name'], $columns);

            // Return success response.
            return ApiResponse::success($vendor, 'Vendor retrieved successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error retrieving vendor: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function store(StoreVendorDetailsRequest $request): JsonResponse
    {
        try {
            // Check for existing vendor
            $existingVendor = $this->vendorService->searchBy('user_id', $request->input('user_id'));

            if ($existingVendor) {
                return ApiResponse::error('This user already has a vendor account', 400, [], [
                    $existingVendor
                ]);
            }

            DB::beginTransaction();

            try {
                if ($request->user()?->role?->name == 'admin') {
                    $status = $request->input('status') ?? null;
                } else {
                    $status = 'PENDDING';
                }
                // Handle file uploads
                $data = [
                    'user_id' => $request->input('user_id'),
                    'business_name' => $request->input('business_name'),
                    'business_description' => $request->input('business_description'),
                    'documentation_url' => $this->vendorService->storeFile(
                        $request->file('documentation'),
                        'vendors/documentations'
                    ),
                    'logo_url' => $request->hasFile('logo')
                        ? $this->vendorService->storeFile(
                            $request->file('logo'),
                            'vendors/logos'
                        )
                        : null,
                    'status' => $status,
                    'approved_at' => $status == 'APPROVED' ? Carbon::now() : null,
                ];

                // Soft delete existing pending/suspended/rejected vendor if exists
                if ($existingVendor) {
                    $oldLogoPath = $this->vendorService->getPath($existingVendor->logo_url, 'vendors/logos/');
                    $this->vendorService->deleteFile($oldLogoPath);
                    $this->vendorService->delete($existingVendor->id);
                }

                // Create new vendor
                $vendor = $this->vendorService->create($data);

                DB::commit();

                // Send notification to admin
                // event(new NewVendorSubmission($vendor));

                return ApiResponse::success(
                    $vendor,
                    'Vendor application submitted successfully. Please wait for approval confirmation.',
                    201
                );
            } catch (Exception $e) {
                DB::rollBack();

                // Clean up uploaded files if transaction fails
                if (isset($data['documentation_url'])) {
                    $oldLogoPath = $this->vendorService->getPath($data['documentation_url'], 'vendors/documentations/');
                    $this->vendorService->deleteFile($oldLogoPath);
                }
                if (isset($data['logo_url'])) {
                    $oldLogoPath = $this->vendorService->getPath($data['logo_url'], 'vendors/logos/');
                    $this->vendorService->deleteFile($oldLogoPath);
                }

                Log::error("Vendor approval processing failed", [
                    'user_id' => $request->input('user_id'),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return ApiResponse::error(
                    'Failed to process vendor application. Please try again.',
                    500
                );
            }
        } catch (Exception $e) {
            Log::error("Vendor approval system error", [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ApiResponse::error(
                'An unexpected error occurred.',
                500
            );
        }
    }

    public function update(UpdateVendorDetailsRequest $request, string $id): JsonResponse
    {
        try {
            if (Str::isUuid($id)) {
                $vendor =  $this->vendorService->searchBy('user_id', $id);
            } else {
                $vendor = $this->vendorService->getVendorById($id);
            }

            if (!$vendor) {
                return ApiResponse::error('Vendor not found', 404);
            }

            // Check if vendor is allowed to update
            if ($request->user()?->role?->name !== 'admin' && in_array($vendor->status, ['SUSPENDED', 'REJECTED'])) {
                return ApiResponse::error('Suspended and Rejected vendors cannot update their profile', 403);
            }

            DB::beginTransaction();

            try {

                $oldLogoPath = null;

                if ($request->user()?->role?->name == 'admin') {
                    $status = $request->input('status') ?? $vendor->status;
                } else {
                    $status = $vendor->status;
                }
                // Handle file uploads
                $data = [
                    'user_id' => $request->input('user_id') ?? $vendor->user_id,
                    'business_name' => $request->input('business_name') ?? $vendor->business_name,
                    'business_description' => $request->input('business_description') ?? $vendor->business_description,
                    'status' => $status,
                    'approved_at' => $status == 'APPROVED' ? Carbon::now() : null,
                ];

                if ($request->hasFile('documentation')) {
                    $oldDocumentationPath = $this->vendorService->getPath($vendor->documentation_url, 'vendors/documentations/');
                    $data['documentation_url'] = $this->vendorService->storeFile(
                        $request->file('documentation'),
                        'vendors/logos/'
                    );

                    // Send notification to admin that user update his documents
                    // event(new NewVendorSubmission($vendor)); 
                }

                // Handle logo upload
                if ($request->hasFile('logo')) {
                    $oldLogoPath = $this->vendorService->getPath($vendor->logo_url, 'vendors/logos/');
                    $data['logo_url'] = $this->vendorService->storeFile(
                        $request->file('logo'),
                        'vendors/logos/'
                    );
                }

                // Update vendor
                $updatedVendor = $this->vendorService->update($vendor->id, $data);

                // Delete old logo if new one was uploaded
                if ($oldLogoPath && $updatedVendor) {
                    $this->vendorService->deleteFile($oldLogoPath);
                }

                DB::commit();

                return ApiResponse::success(
                    $updatedVendor,
                    'Vendor information updated successfully'
                );
            } catch (Exception $e) {
                DB::rollBack();

                // Clean up uploaded file if transaction fails
                if (isset($data['documentation_url'])) {
                    $oldDocumentationPath = $this->vendorService->getPath($data['documentation_url'], 'vendors/documentations/');
                    $this->vendorService->deleteFile($oldDocumentationPath);
                }

                if (isset($data['logo_url'])) {
                    $oldLogoPath = $this->vendorService->getPath($data['logo_url'], 'vendors/logos/');
                    $this->vendorService->deleteFile($oldLogoPath);
                }

                Log::error("Vendor update failed", [
                    'vendor_id' => $id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return ApiResponse::error(
                    //'Failed to update vendor information. Please try again.',
                    $e->getMessage(),
                    500
                );
            }
        } catch (Exception $e) {
            Log::error("Vendor update system error", [
                'vendor_id' => $id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ApiResponse::error(
                $e->getMessage(),
                500
            );
        }
    }

    public function updateBulk(Request $request): JsonResponse
    {
        try {
            // Validate input parameters.
            $validator = Validator::make($request->all(), [
                'status' => 'required|string|in:PENDDING,APPROVED,SUSPENDED',
                'created_at'  => 'sometimes|date',
                'updated_at'  => 'sometimes|date',

                'conditions'  => 'sometimes|array',
                'columns'     => 'sometimes|array',
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("Vendors updating validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            };
            $validated = $validator->validated();

            $conditions = $validator->validated()['conditions'] ?? [];
            $columns = $validator->validated()['columns'] ?? ['*'];

            // Convert created_at & updated_at if provided
            if (!empty($validated['created_at'])) {
                $validated['created_at'] = Carbon::parse($validated['created_at'])->format('Y-m-d H:i:s');
            }

            if (!empty($validated['updated_at'])) {
                $validated['updated_at'] = Carbon::parse($validated['updated_at'])->format('Y-m-d H:i:s');
            }

            // if (!empty($validated['deleted_at'])) {
            //     $validated['deleted_at'] = Carbon::parse($validated['deleted_at'])->format('Y-m-d H:i:s');
            // }

            // Filter only valid vendor fields (excluding 'columns')
            $data = array_filter($validated, function ($key) {
                return !in_array($key, ['columns', 'conditions']); // Exclude 'columns' key and conditions key
            }, ARRAY_FILTER_USE_KEY);

            // Call update function with or without columns
            $vendors = $this->vendorService->updateGroup($data, $conditions, $columns);

            // Return success response.
            return ApiResponse::success($vendors, 'Vendor updated successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error updating vendor: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function delete(ValidateColumnAndConditionRequest $request, string $id)
    {
        try {
            $forceDelete = $request->validated()['force'] ?? false;

            // Validate the ID format (either UUID for vendor_id or user_id)
            if (Str::isUuid($id)) {
                $vendor =  $this->vendorService->searchBy('user_id', $id);
                $vendor = $this->vendorService->delete($vendor->id, $forceDelete);
            } else {
                $vendor = $this->vendorService->delete($id, $forceDelete);
            }

            return $forceDelete ?
                ApiResponse::success($vendor, 'Vendor permenantly deleted successfully.') :
                ApiResponse::success($vendor, 'Vendor soft deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting vendor: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function deleteBulk(ValidateColumnAndConditionRequest $request)
    {
        try {
            $conditions = $request->validated()['conditions'] ?? [];
            $forceDelete = $request->validated()['force'] ?? false;

            $deletedVendors = $this->vendorService->deleteBulk($conditions, $forceDelete);

            return $forceDelete ?
                ApiResponse::success($deletedVendors, 'Vendors permenantly deleted successfully.') :
                ApiResponse::success($deletedVendors, 'Vendors soft deleted successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error deleting vendors: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function isSoftDeleted(string $id)
    {
        try {

            // Validate the ID format (either UUID for vendor_id or user_id)
            if (Str::isUuid($id)) {
                $vendor =  $this->vendorService->searchBy('user_id', $id);
                $isDeleted = $this->vendorService->softDeleted($vendor->id);
            } else {
                $isDeleted = $this->vendorService->softDeleted($id);
            }

            return $isDeleted ?
                ApiResponse::success($isDeleted, 'Vendor is soft deleted') :
                ApiResponse::success($isDeleted, 'Vendor is not soft deleted');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error checking soft deleted vendor: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function restore(ValidateColumnAndConditionRequest $request, string $id)
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];

            // Validate the ID format (either UUID for vendor_id or user_id)
            if (Str::isUuid($id)) {
                $vendor = $this->vendorService->getAllVendors(
                    onlyTrashed: true,
                    conditions: ['user_id' => $id]
                )->first();
                $vendor = $this->vendorService->restore($vendor->id, $columns);
            } else {
                $vendor = $this->vendorService->restore($id, $columns);
            }

            return ApiResponse::success($vendor, 'Vendor is restored');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error restoring soft deleted vendor: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function restoreBulk(ValidateColumnAndConditionRequest $request)
    {
        try {
            $conditions = $request->validated()['conditions'] ?? [];
            $columns = $request->validated()['columns'] ?? ['*'];

            $vendors = $this->vendorService->restoreBulk($conditions, $columns);

            return ApiResponse::success($vendors, 'Vendor is restored');
        } catch (Exception $e) {
            Log::error("Error restoring vendors: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function isApproved(string $id)
    {
        try {
            // Validate the ID format (either UUID for vendor_id or user_id)
            if (Str::isUuid($id)) {
                $vendor =  $this->vendorService->searchBy('user_id', $id);
            } else {
                $vendor = $this->vendorService->getVendorById($id);
            }

            if (!$vendor) {
                return ApiResponse::error('Vendor not found', 404);
            }

            // Define possible status responses
            $statusMessages = [
                'APPROVED' => [
                    'message' => 'Vendor is approved and active',
                    'status' => true
                ],
                'PENDING' => [
                    'message' => 'Vendor application is pending review',
                    'status' => false
                ],
                'SUSPENDED' => [
                    'message' => 'Vendor account is suspended',
                    'status' => false
                ],
                'REJECTED' => [
                    'message' => 'Vendor application was rejected',
                    'status' => false
                ]
            ];

            // Get the appropriate response based on status
            $response = $statusMessages[$vendor->status] ?? [
                'message' => 'Unknown vendor status',
                'status' => false
            ];

            return ApiResponse::success([
                'is_approved' => $response['status'],
                'status' => $vendor->status,
                'details' => $response['message'],
                'vendor_id' => $vendor->id,
                'last_updated' => $vendor->updated_at->toIso8601String()
            ]);
        } catch (Exception $e) {
            Log::error("Vendor approval check failed", [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ApiResponse::error(
                'Unable to check vendor status. Please try again later.',
                500
            );
        }
    }
}