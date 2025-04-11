<?php

namespace App\Http\Controllers\Api\Admin;

use Exception;
use Illuminate\Http\Request;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaginateRequest;
use App\Http\Requests\StoreRoleRequest;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ValidateColumnAndConditionRequest;

class RoleController extends Controller
{
    /**
     * Constructor to inject the RoleService dependency.
     *
     * @param RoleService $roleService The service responsible for role-related operations.
     */
    public function __construct(protected RoleService $roleService) {}

    public function index(PaginateRequest $request): JsonResponse
    {

        try {
            $validated = $request->validated();
            $paginate = $validated['paginate'] ?? false;
            $withTrashed = $validated['with_trashed'] ?? false;
            $onlyTrashed = $validated['only_trashed'] ?? false;
            $conditions = $validated['conditions'] ?? [];
            $columns = $validated['columns'] ?? ['*'];

            $roles = $paginate
                ? $this->roleService->getAllRoles(
                    perPage: $validated['per_page'] ?? 15, // Default to 15 if not specified.
                    columns: $columns,
                    pageName: $validated['pageName'] ?? 'page',
                    page: $validated['page'] ?? 1,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                )
                : $this->roleService->getAllRoles(
                    columns: $columns,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                );

            return ApiResponse::success($roles, 'Roles retrieved successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function show(ValidateColumnAndConditionRequest $request, string $id): JsonResponse
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];

            $role = $this->roleService->getRoleById($id, $columns);

            return ApiResponse::success($role, 'Role retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving role: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $role = $this->roleService->create($validated);

            return ApiResponse::success($role, 'Role created successfully.');
        } catch (Exception $e) {
            Log::error("Error creating role: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'string|unique:roles,name|max:255',
                'description' => 'string|unique:roles,description|max:255',
                'columns'  => 'sometimes|array',
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("Role updating validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }
            // Extract validated data.
            $validatedData = $request->except(['columns']);
            $columns = empty($request->only(['columns'])) ? ['*'] : $request->only(['columns']);

            $role = $this->roleService->update($id, $validatedData, $columns);

            return ApiResponse::success($role, 'Role updated successfully.');
        } catch (Exception $e) {
            Log::error("Error updating role: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function delete(ValidateColumnAndConditionRequest $request, string $id)
    {
        try {
            $forceDelete = $request->validated()['force'] ?? false;

            $role = $this->roleService->delete($id, $forceDelete);

            return $forceDelete ?
                ApiResponse::success($role, 'Role permenantly deleted successfully.') :
                ApiResponse::success($role, 'Role soft deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting role: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function isSoftDeleted(string $id)
    {
        try {
            $isDeleted = $this->roleService->softDeleted($id);

            return $isDeleted ?
                ApiResponse::success($isDeleted, 'Role is soft deleted') :
                ApiResponse::success($isDeleted, 'Role is not soft deleted');
        } catch (Exception $e) {
            Log::error("Error checking soft deleted role: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function restore(ValidateColumnAndConditionRequest $request, string $id)
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];

            $role = $this->roleService->restore($id, $columns);

            return ApiResponse::success($role, 'Role is restored');
        } catch (Exception $e) {
            Log::error("Error restoring soft deleted role: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}