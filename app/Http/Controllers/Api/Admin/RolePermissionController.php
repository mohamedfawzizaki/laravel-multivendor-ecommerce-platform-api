<?php

namespace App\Http\Controllers\Api\Admin;

use Exception;
use App\Models\Role;
use App\Models\Permission;
use App\Services\RoleService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;


class RolePermissionController extends Controller
{
    public function __construct(public PermissionService $permissionService, public RoleService $roleService) {}

    public function assignPermissionByID(string $roleID, string $permissionID): JsonResponse
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

    public function assignPermissionByName(string $role_name, string $permission_name): JsonResponse
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

    public function removePermissionByID(string $roleID, string $permissionID): JsonResponse
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

            // remove the permission to the role 
            $role->permissions()->detach($permissionID);

            return ApiResponse::success(
                [
                    'role' => $role->name,
                    'permission' => $permission->name
                ],
                'Permission removed successfully!'
            );
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            return ApiResponse::error($exception->getMessage(), 500);
        }
    }

    public function removePermissionByName(string $role_name, string $permission_name): JsonResponse
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

            // remove the permission to the role 
            $role->permissions()->detach($permission->id);

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

    public function assignRoleToUser(string $roleName, string $userID, UserService $userService, RoleService $roleService): JsonResponse
    {
        try {
            // Find the role by name
            // $role = Role::where('name', $roleName)->first();

            $role = $roleService->getAllRoles(conditions: ["name:=:$roleName"]);

            if (!$role) {
                return ApiResponse::error('Role not found', 404);
            }

            $user = $userService->update($userID, ['role_id' => $role->first()->id]);

            return ApiResponse::success(
                $user,
                'Role assigned successfully!'
            );
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            return ApiResponse::error($exception->getMessage(), 500);
        }
    }
}
