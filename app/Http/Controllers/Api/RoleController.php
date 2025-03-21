<?php

namespace App\Http\Controllers\Api;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Repositories\Eloquent\RoleRepository;

class RoleController extends Controller
{
    protected RoleRepository $roleRepository;

    public function __construct(RoleRepository $roleRepository)
    {
        $this->roleRepository = $roleRepository;
    }

    public function index(Request $request): JsonResponse
    {
        // query string
        // return response()->json($this->roleRepository->findByEmail((Role::where('rolename', $request->query('rolename'))->first()->email)));
        return response()->json($this->roleRepository->getAll());
    }

    public function show($id): JsonResponse
    {
        $role = $this->roleRepository->findById($id);
        return $role ? response()->json($role) : response()->json(['error' => 'Role not found'], 404);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $role = $this->roleRepository->create($validatedData);
        return $role ? response()->json($role, 201) : response()->json(['error' => 'Role creation failed'], 400);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $role = $this->roleRepository->update($id, $request->all());
        return $role ? response()->json($role) : response()->json(['error' => 'Role update failed'], 400);
    }
    public function edit(Request $request, string $id): JsonResponse
    {
        $role = $this->roleRepository->update($id, $request->all());
        return $role ? response()->json($role) : response()->json(['error' => 'Role update failed'], 400);
    }

    public function delete(string $id): JsonResponse
    {
        $deleted = $this->roleRepository->delete($id);

        if (!$deleted) {
            return response()->json(['error' => 'Role not found or deletion failed'], 404);
        }
        return response()->json(['message' => 'Role deleted'], 200);
    }
}