<?php

namespace App\Http\Controllers\Api;

use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStatusRequest;
use App\Repositories\Eloquent\StatusRepository;

class StatusController extends Controller
{
    protected StatusRepository $statusRepository;

    public function __construct(StatusRepository $statusRepository)
    {
        $this->statusRepository = $statusRepository;
    }

    public function index(Request $request): JsonResponse
    {
        // query string
        // return response()->json($this->statusRepository->findByEmail((Status::where('statusname', $request->query('statusname'))->first()->email)));
        return response()->json($this->statusRepository->getAll());
    }

    public function show($id): JsonResponse
    {
        $status = $this->statusRepository->findById($id);
        return $status ? response()->json($status) : response()->json(['error' => 'Status not found'], 404);
    }

    public function store(StoreStatusRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $status = $this->statusRepository->create($validatedData);
        return $status ? response()->json($status, 201) : response()->json(['error' => 'Status creation failed'], 400);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $status = $this->statusRepository->update($id, $request->all());
        return $status ? response()->json($status) : response()->json(['error' => 'Status update failed'], 400);
    }
    public function edit(Request $request, string $id): JsonResponse
    {
        $status = $this->statusRepository->update($id, $request->all());
        return $status ? response()->json($status) : response()->json(['error' => 'Status update failed'], 400);
    }

    public function delete(string $id): JsonResponse
    {
        $deleted = $this->statusRepository->delete($id);

        if (!$deleted) {
            return response()->json(['error' => 'Status not found or deletion failed'], 404);
        }
        return response()->json(['message' => 'Status deleted'], 200);
    }
}