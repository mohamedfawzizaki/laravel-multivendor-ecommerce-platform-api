<?php

namespace App\Http\Controllers\Api\Admin;

use Exception;
use Illuminate\Http\Request;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaginateRequest;
use App\Http\Requests\StorePermissionRequest;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{
    /**
     * Constructor to inject the PermissionService dependency.
     *
     * @param PermissionService $permissionService The service responsible for permission-related operations.
     */
    public function __construct(protected PermissionService $permissionService) {}

    public function index(PaginateRequest $request): JsonResponse
    {

        try {
            $validated = $request->validated();
            $paginate = $validated['paginate'] ?? false;
            $withTrashed = $validated['with_trashed'] ?? false;
            $onlyTrashed = $validated['only_trashed'] ?? false;
            $conditions = $validated['conditions'] ?? [];
            $columns = $validated['columns'] ?? ['*'];

            $permissions = $paginate
                ? $this->permissionService->getAllPermissions(
                    perPage: $validated['per_page'] ?? 15, // Default to 15 if not specified.
                    columns: $columns,
                    pageName: $validated['pageName'] ?? 'page',
                    page: $validated['page'] ?? 1,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                )
                : $this->permissionService->getAllPermissions(
                    columns: $columns,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                );

            return ApiResponse::success($permissions, 'Permissions retrieved successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $data = array_merge($request->all(), ['id' => $id]);

            $validator = Validator::make($data, [
                'id' => 'required|string',
                'columns' => 'sometimes|array',
            ]);

            if ($validator->fails()) {
                Log::warning("Permission retrieval validation failed.", [
                    'errors' => $validator->errors(),
                    'input' => $data,
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }

            $validated = $validator->validated();
            $columns = $validated['columns'] ?? ['*'];

            $permission = $this->permissionService->getPermissionById($validated['id'], $columns);

            return ApiResponse::success($permission, 'Permission retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving permission: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function store(StorePermissionRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $permission = $this->permissionService->create($validated);

            return ApiResponse::success($permission, 'Permission created successfully.');
        } catch (Exception $e) {
            Log::error("Error creating permission: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $idAndColumns = array_merge($request->all(), ['id' => $id]);
            $validatorForidAndColumns = Validator::make($idAndColumns, [
                'id' => 'required|string',
                'columns'  => 'sometimes|array',
            ]);

            if ($validatorForidAndColumns->fails()) {
                Log::warning("Permission updating validation failed.", [
                    'errors' => $validatorForidAndColumns->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validatorForidAndColumns->errors()
                );
            }

            $validatorForDataToUpdate = Validator::make($request->all(), [
                'name' => 'string|unique:permissions,name|max:255',
                'description' => 'string|unique:permissions,description|max:255',
            ]);

            if ($validatorForDataToUpdate->fails()) {
                Log::warning("Permission updating validation failed.", [
                    'errors' => $validatorForDataToUpdate->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validatorForDataToUpdate->errors()
                );
            }
            $validatedData = $validatorForDataToUpdate->validated();

            $id = $validatorForidAndColumns->validated()['id'];
            $columns = $validatorForidAndColumns->validated()['columns'] ?? ['*'];

            $permission = $this->permissionService->update($id, $validatedData, $columns);

            return ApiResponse::success($permission, 'Permission updated successfully.');
        } catch (Exception $e) {
            Log::error("Error updating permission: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function delete(Request $request, string $id)
    {
        try {
            $data = array_merge($request->all(), ['id' => $id]);
            $validator = Validator::make($data, [
                'id' => 'required|string',
                'force' => 'sometimes|accepted',
            ]);

            if ($validator->fails()) {
                Log::warning("Permission updating validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }
            $validated = $validator->validated();
            $forceDelete = $validated['force'] ?? false;

            $permission = $this->permissionService->delete($validated['id'], $forceDelete);

            return $forceDelete ?
                ApiResponse::success($permission, 'Permission permenantly deleted successfully.') :
                ApiResponse::success($permission, 'Permission soft deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting permission: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function isSoftDeleted(string $id)
    {
        try {
            $validator = Validator::make(['id' => $id], [
                'id' => 'required|string'
            ]);

            if ($validator->fails()) {
                Log::warning("Permission checking validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }

            $id = $validator->validated()['id'];

            $isDeleted = $this->permissionService->softDeleted($id);

            return $isDeleted ?
                ApiResponse::success($isDeleted, 'Permission is soft deleted') :
                ApiResponse::success($isDeleted, 'Permission is not soft deleted');
        } catch (Exception $e) {
            Log::error("Error checking soft deleted permission: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }
    public function restore(Request $request, string $id)
    {
        try {
            $data = array_merge($request->all(), ['id' => $id]);
            $validator = Validator::make($data, [
                'id' => 'required|string',
                'columns'  => 'sometimes|array',
            ]);

            if ($validator->fails()) {
                Log::warning("Permission restoring validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }

            $id = $validator->validated()['id'];
            $columns = $validator->validated()['columns'] ?? ['*'];

            $permission = $this->permissionService->restore($id, $columns);

            return ApiResponse::success($permission, 'Permission is restored');
        } catch (Exception $e) {
            Log::error("Error restoring soft deleted permission: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}