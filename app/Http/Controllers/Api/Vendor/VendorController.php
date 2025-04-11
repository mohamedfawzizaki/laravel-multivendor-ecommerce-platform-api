<?php

namespace App\Http\Controllers\Api\Vendor;

use Exception;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Services\VendorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
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