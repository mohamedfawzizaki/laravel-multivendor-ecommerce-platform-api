<?php

namespace App\Http\Controllers\Api\Admin;

use Exception;
use Illuminate\Http\Request;
use App\Services\StatusService;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaginateRequest;
use App\Http\Requests\StoreStatusRequest;
use Illuminate\Support\Facades\Validator;

class StatusController extends Controller
{
    /**
     * Constructor to inject the StatusService dependency.
     *
     * @param StatusService $statusService The service responsible for status-related operations.
     */
    public function __construct(protected StatusService $statusService) {}

    public function index(PaginateRequest $request): JsonResponse
    {

        try {
            $validated = $request->validated();
            $paginate = $validated['paginate'] ?? false;
            $withTrashed = $validated['with_trashed'] ?? false;
            $onlyTrashed = $validated['only_trashed'] ?? false;
            $conditions = $validated['conditions'] ?? [];
            $columns = $validated['columns'] ?? ['*'];

            $statuss = $paginate
                ? $this->statusService->getAllStatuss(
                    perPage: $validated['per_page'] ?? 15, // Default to 15 if not specified.
                    columns: $columns,
                    pageName: $validated['pageName'] ?? 'page',
                    page: $validated['page'] ?? 1,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                )
                : $this->statusService->getAllStatuss(
                    columns: $columns,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                );

            return ApiResponse::success($statuss, 'Statuss retrieved successfully.');
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
                Log::warning("Status retrieval validation failed.", [
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

            $status = $this->statusService->getStatusById($validated['id'], $columns);

            return ApiResponse::success($status, 'Status retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving status: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function store(StoreStatusRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $status = $this->statusService->create($validated);

            return ApiResponse::success($status, 'Status created successfully.');
        } catch (Exception $e) {
            Log::error("Error creating status: {$e->getMessage()}", ['exception' => $e]);

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
                Log::warning("Status updating validation failed.", [
                    'errors' => $validatorForidAndColumns->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validatorForidAndColumns->errors()
                );
            }

            $validatorForDataToUpdate = Validator::make($request->all(), [
                'name' => 'string|unique:statuses,name|max:255',
                'description' => 'string|unique:statuses,description|max:255',
            ]);

            if ($validatorForDataToUpdate->fails()) {
                Log::warning("Status updating validation failed.", [
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

            $status = $this->statusService->update($id, $validatedData, $columns);

            return ApiResponse::success($status, 'Status updated successfully.');
        } catch (Exception $e) {
            Log::error("Error updating status: {$e->getMessage()}", ['exception' => $e]);

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
                Log::warning("Status updating validation failed.", [
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

            $status = $this->statusService->delete($validated['id'], $forceDelete);

            return $forceDelete ?
                ApiResponse::success($status, 'Status permenantly deleted successfully.') :
                ApiResponse::success($status, 'Status soft deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting status: {$e->getMessage()}", ['exception' => $e]);

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
                Log::warning("Status checking validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }

            $id = $validator->validated()['id'];

            $isDeleted = $this->statusService->softDeleted($id);

            return $isDeleted ?
                ApiResponse::success($isDeleted, 'Status is soft deleted') :
                ApiResponse::success($isDeleted, 'Status is not soft deleted');
        } catch (Exception $e) {
            Log::error("Error checking soft deleted status: {$e->getMessage()}", ['exception' => $e]);

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
                Log::warning("Status restoring validation failed.", [
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

            $status = $this->statusService->restore($id, $columns);

            return ApiResponse::success($status, 'Status is restored');
        } catch (Exception $e) {
            Log::error("Error restoring soft deleted status: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}