<?php

namespace App\Http\Controllers\Api\Admin;

use Exception;
use Carbon\Carbon;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Rules\StrongPassword;
use App\Services\RoleService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaginateRequest;
use App\Http\Requests\StoreUserRequest;
use Illuminate\Support\Facades\Validator;

class RolePermissionController extends Controller
{
    public function __construct(public PermissionService $permissionService, public RoleService $roleService) {}

    public function assignPermissionByID(string $roleID, string $permissionID)
    {
        try {
            // $role = $this->roleService->getRoleById($roleID);
            // $permission = $this->permissionService->getPermissionById($permissionID);

            $role = Role::find($roleID);
            $permission = Permission::find($permissionID);

            if (!$role) {
                return ApiResponse::error('Role not found', 404);
            }

            if (!$permission) {
                return ApiResponse::error('Permission not found', 404);
            }

            // Attach the permission to the role without duplicates
            $role->permissions()->syncWithoutDetaching([$permission->id]);

            return ApiResponse::success(
                [
                    'role' => $role->name,
                    'permission' => $permission->name
                ],
                'Permission assigned successfully!'
            );
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            return ApiResponse::error($exception->getMessage(), 500);
        }
    }

    public function assignPermissionByName(string $role_name, string $permission_name)
    {
        try {
            // $role = $this->roleService->getRoleById($roleID);
            // $permission = $this->permissionService->getPermissionById($permissionID);

            // Find the role by name or fail
            $role = Role::where('name', $role_name)->first();

            // Find the permission by name or fail
            $permission = Permission::where('name', $permission_name)->first();

            if (!$role) {
                return ApiResponse::error('Role not found', 404);
            }

            if (!$permission) {
                return ApiResponse::error('Permission not found', 404);
            }

            // Attach the permission to the role without duplicates
            $role->permissions()->syncWithoutDetaching([$permission->id]);

            return ApiResponse::success(
                [
                    'role' => $role->name,
                    'permission' => $permission->name
                ],
                'Permission assigned successfully!'
            );
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            return ApiResponse::error($exception->getMessage(), 500);
        }
    }
}