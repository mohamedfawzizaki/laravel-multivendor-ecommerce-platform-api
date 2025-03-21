<?php

namespace App\Http\Controllers\Api;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Rules\StrongPassword;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaginateRequest;
use App\Http\Requests\StoreUserRequest;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Constructor to inject the UserService dependency.
     *
     * @param UserService $userService The service responsible for user-related operations.
     */
    public function __construct(protected UserService $userService) {}

    /**
     * Handles the retrieval of users, either paginated or non-paginated.
     *
     * This method processes the request to fetch users based on the provided
     * pagination parameters. If pagination is not requested, it retrieves all users.
     * 
     * @param PaginateRequest $request The validated request containing pagination parameters.
     * @return JsonResponse The JSON response with retrieved users or an error message.
     */
    public function index(PaginateRequest $request): JsonResponse
    {
        try {
            // Extract validated input values with default fallbacks.
            $validated = $request->validated();
            $paginate = $validated['paginate'] ?? false;
            $columns = $validated['columns'] ?? null;
            $withTrashed = $validated['with_trashed'] ?? false;
            $onlyTrashed = $validated['only_trashed'] ?? false;
            $conditions = $validated['conditions'] ?? null;


            // var_dump($conditions);
            // Retrieve users based on pagination preference.
            $users = $paginate
                ? $this->userService->getAllUsers(
                    perPage: $validated['per_page'] ?? 15, // Default to 15 if not specified.
                    columns: $columns,
                    pageName: $validated['pageName'] ?? 'page',
                    page: $validated['page'] ?? 1,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                )
                : $this->userService->getAllUsers(
                    columns: $columns,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                );

            // Return a success response with the retrieved users.
            return ApiResponse::success($users, 'Users retrieved successfully.');
        } catch (Exception $e) {
            // Handle any exceptions and return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Retrieves a user by ID with optional specific columns.
     *
     * Validates the request parameters, retrieves the user from the service, and 
     * returns an appropriate response. Logs warnings for validation failures.
     *
     * @param Request $request The HTTP request containing optional columns.
     * @param string $id The ID of the user to retrieve.
     * @return JsonResponse The JSON response containing the user data or an error message.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            // Merge request data with the provided ID for validation.
            $data = array_merge($request->all(), ['id' => $id]);

            // Validate input parameters.
            $validator = Validator::make($data, [
                'id' => 'required|string|uuid', // Ensures a valid UUID format.
                'columns' => 'sometimes|array', // Optional columns parameter.
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("User retrieval validation failed.", [
                    'errors' => $validator->errors(),
                    'input' => $data, // Log the provided input for debugging.
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }

            // Extract validated data.
            $validated = $validator->validated();
            $columns = $validated['columns'] ?? null;

            // Retrieve the user.
            $user = $this->userService->getUserById($validated['id'], $columns);

            // Return success response.
            return ApiResponse::success($user, 'User retrieved successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error retrieving user: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function searchBy(Request $request): JsonResponse
    {
        try {
            // Validate input parameters.
            $validator = Validator::make($request->all(), [
                'name' => 'required_without:email|string|exists:users,name',
                'email' => 'required_without:name|email|exists:users,email',
                'columns' => 'sometimes|array', // Optional columns parameter.
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("User retrieval validation failed.", [
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
            $searchBy = ($validated['email'] ?? null) ? 'email' : 'name';
            $columns = $validated['columns'] ?? null;

            $user = $this->userService->searchBy($searchBy, $validated[$searchBy], $columns);

            // Return success response.
            return ApiResponse::success($user, 'User retrieved successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error retrieving user: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            // Extract validated data.
            $validated = $request->validated();

            $user = $this->userService->create($validated);

            // Return success response.
            return ApiResponse::success($user, 'User created successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error creating user: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            // Merge request data with the provided ID for validation.
            $data = array_merge($request->all(), ['id' => $id]);
            // Validate input parameters.
            $validator = Validator::make($data, [
                'id' => 'required|string|uuid', // Ensures a valid UUID format.
                'name' => 'string|unique:users,name|max:255',
                'email' => 'email|unique:users,email',
                'password' => ['sometimes', 'confirmed',  new StrongPassword],
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("User updating validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }
            // Extract validated data.
            $validated = $validator->validated();

            $user = $this->userService->update($id, $validated);

            // Return success response.
            return ApiResponse::success($user, 'User updated successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error updating user: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function updateBulk(Request $request): JsonResponse
    {
        try {
            // Validate input parameters.
            $validator = Validator::make($request->all(), [
                'password'    => ['sometimes', 'confirmed', new StrongPassword],
                'created_at'  => 'sometimes|date',
                'updated_at'  => 'sometimes|date',
                'deleted_at'  => 'sometimes|date',

                'conditions'  => 'sometimes|array',
                'columns'     => 'sometimes|array',
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("Users updating validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            };
            $validated = $validator->validated();

            $conditions = $validated['conditions'] ?? null;
            $columns = $validated['columns'] ?? null;

            // Convert created_at & updated_at if provided
            if (!empty($validated['created_at'])) {
                $validated['created_at'] = Carbon::parse($validated['created_at'])->format('Y-m-d H:i:s');
            }

            if (!empty($validated['updated_at'])) {
                $validated['updated_at'] = Carbon::parse($validated['updated_at'])->format('Y-m-d H:i:s');
            }

            if (!empty($validated['deleted_at'])) {
                $validated['deleted_at'] = Carbon::parse($validated['deleted_at'])->format('Y-m-d H:i:s');
            }

            // Filter only valid user fields (excluding 'columns')
            $data = array_filter($validated, function ($key) {
                return !in_array($key, ['columns', 'conditions']); // Exclude 'columns' key and conditions key
            }, ARRAY_FILTER_USE_KEY);

            // Call update function with or without columns
            $users = $this->userService->updateGroup($data, $conditions, $columns);

            // Return success response.
            return ApiResponse::success($users, 'User updated successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error updating user: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function delete(Request $request, string $id)
    {
        try {
            // Merge request data with the provided ID for validation.
            $data = array_merge($request->all(), ['id' => $id]);
            // Validate input parameters.
            $validator = Validator::make($data, [
                'id' => 'required|string|uuid', // Ensures a valid UUID format.
                'force' => 'sometimes|accepted',
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("User updating validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }
            // Extract validated data.
            $validated = $validator->validated();
            $forceDelete = $validated['force'] ?? false;

            $user = $this->userService->delete($validated['id'], $forceDelete);

            // Return success response.
            return $forceDelete ?
                ApiResponse::success($user, 'User permenantly deleted successfully.') :
                ApiResponse::success($user, 'User soft deleted successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error deleting user: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function deleteBulk(Request $request)
    {
        try {
            // Validate input parameters.
            $validator = Validator::make($request->all(), [
                'conditions'  => 'required|array',
                'force' => 'sometimes|accepted',
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("Users deletion validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }
            // Extract validated data.
            $validated = $validator->validated();
            $conditions = $validated['conditions'] ?? false;
            $forceDelete = $validated['force'] ?? false;

            $deletedUsers = $this->userService->deleteBulk($conditions, $forceDelete);

            // Return success response.
            return $forceDelete ?
                ApiResponse::success($deletedUsers, 'Users permenantly deleted successfully.') :
                ApiResponse::success($deletedUsers, 'Users soft deleted successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error deleting users: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function IsSoftDeleted() {}

    public function restore() {}

    public function restoreBulk() {}
}
