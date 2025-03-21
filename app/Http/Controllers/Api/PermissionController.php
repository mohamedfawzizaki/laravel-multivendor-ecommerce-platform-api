<?php

namespace App\Http\Controllers\Api;

use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePermissionRequest;
use App\Repositories\Eloquent\PermissionRepository;

class PermissionController extends Controller
{
    protected PermissionRepository $permissionRepository;

    public function __construct(PermissionRepository $permissionRepository)
    {
        $this->permissionRepository = $permissionRepository;
    }

    public function index(Request $request): JsonResponse
    {
        // query string
        // return response()->json($this->permissionRepository->findByEmail((Permission::where('permissionname', $request->query('permissionname'))->first()->email)));
        return response()->json($this->permissionRepository->getAll());
    }

    public function show($id): JsonResponse
    {
        $permission = $this->permissionRepository->findById($id);
        return $permission ? response()->json($permission) : response()->json(['error' => 'Permission not found'], 404);
    }

    public function store(StorePermissionRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $permission = $this->permissionRepository->create($validatedData);
        return $permission ? response()->json($permission, 201) : response()->json(['error' => 'Permission creation failed'], 400);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $permission = $this->permissionRepository->update($id, $request->all());
        return $permission ? response()->json($permission) : response()->json(['error' => 'Permission update failed'], 400);
    }
    public function edit(Request $request, string $id): JsonResponse
    {
        $permission = $this->permissionRepository->update($id, $request->all());
        return $permission ? response()->json($permission) : response()->json(['error' => 'Permission update failed'], 400);
    }

    public function delete(string $id): JsonResponse
    {
        $deleted = $this->permissionRepository->delete($id);

        if (!$deleted) {
            return response()->json(['error' => 'Permission not found or deletion failed'], 404);
        }
        return response()->json(['message' => 'Permission deleted'], 200);
    }
}