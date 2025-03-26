<?php

namespace App\Http\Controllers\Api\User;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\CountryService;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;

use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaginateRequest;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreAddressRequest;

class CountryController extends Controller
{
    /**
     * Constructor to inject the CountryService dependency.
     *
     * @param CountryService $countryService The service responsible for country-related operations.
     */
    public function __construct(protected CountryService $countryService) {}

    public function index(PaginateRequest $request): JsonResponse
    {

        try {
            // Extract validated input values with default fallbacks.
            $validated = $request->validated();
            $paginate = $validated['paginate'] ?? false;
            $withTrashed = $validated['with_trashed'] ?? false;
            $onlyTrashed = $validated['only_trashed'] ?? false;
            $conditions = $validated['conditions'] ?? [];
            $columns = $validated['columns'] ?? ['*'];


            // var_dump($conditions);
            // Retrieve countrys based on pagination preference.
            $countrys = $paginate
                ? $this->countryService->getAllCountrys(
                    perPage: $validated['per_page'] ?? 15, // Default to 15 if not specified.
                    columns: $columns,
                    pageName: $validated['pageName'] ?? 'page',
                    page: $validated['page'] ?? 1,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                )
                : $this->countryService->getAllCountrys(
                    columns: $columns,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                );

            // Return a success response with the retrieved countrys.
            return ApiResponse::success($countrys, 'Countrys retrieved successfully.');
        } catch (Exception $e) {
            // Handle any exceptions and return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function show(Request $request, string $id): JsonResponse
    {
        try {
            // Merge request data with the provided ID for validation.
            $data = array_merge($request->all(), ['id' => $id]);

            // Validate input parameters.
            $validator = Validator::make($data, [
                'id' => 'required|string', 
                'columns' => 'sometimes|array', // Optional columns parameter.
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("Country retrieval validation failed.", [
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
            $columns = $validated['columns'] ?? ['*'];

            // Retrieve the country.
            $country = $this->countryService->getCountryById($validated['id'], $columns);

            // Return success response.
            return ApiResponse::success($country, 'Country retrieved successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error retrieving country: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function searchBy(Request $request): JsonResponse
    {
        try {
            // Validate input parameters.
            $validator = Validator::make($request->all(), [
                'country' => 'required|string|exists:countrys,country',
                'columns' => 'sometimes|array', // Optional columns parameter.
            ], [
                'country'=>'the selected country is invalid or is not found'
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("Country retrieval validation failed.", [
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
            $columns = $validated['columns'] ?? ['*'];

            $country = $this->countryService->searchBy('name', $validated['name'], $columns);

            // Return success response.
            return ApiResponse::success($country, 'Country retrieved successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error retrieving country: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        try {
            // Extract validated data.
            $validated = $request->validated();

            $country = $this->countryService->create($validated);

            // Return success response.
            return ApiResponse::success($country, 'Country created successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error creating country: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            // Merge request data with the provided ID for validation.
            $idAndColumns = array_merge($request->all(), ['id' => $id]);
            $validatorForidAndColumns = Validator::make($idAndColumns, [
                'id' => 'required|string',
                'columns'  => 'sometimes|array',
            ]);
            // Handle validation failures.
            if ($validatorForidAndColumns->fails()) {
                Log::warning("Country updating validation failed.", [
                    'errors' => $validatorForidAndColumns->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validatorForidAndColumns->errors()
                );
            }
            // Validate input parameters.
            $validatorForDataToUpdate = Validator::make($request->all(), [
                'country' => 'sometimes|string|unique:countrys,country|max:30',
                'is_primary' => 'sometimes|boolean',
            ]);

            // Handle validation failures.
            if ($validatorForDataToUpdate->fails()) {
                Log::warning("Country updating validation failed.", [
                    'errors' => $validatorForDataToUpdate->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validatorForDataToUpdate->errors()
                );
            }
            // Extract validated data.
            $validatedData = $validatorForDataToUpdate->validated();
            $id = $validatorForidAndColumns->validated()['id'];
            $columns = $validatorForidAndColumns->validated()['columns'] ?? ['*'];

            $country = $this->countryService->update($id, $validatedData, $columns);

            // Return success response.
            return ApiResponse::success($country, 'Country updated successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error updating country: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function updateBulk(Request $request): JsonResponse
    {
        try {
            // Validate input parameters.
            $validator = Validator::make($request->all(), [
                'created_at'  => 'sometimes|date',
                'updated_at'  => 'sometimes|date',
                'deleted_at'  => 'sometimes|date',

                'conditions'  => 'sometimes|array',
                'columns'     => 'sometimes|array',
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("Countrys updating validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            };
            $validated = $validator->validated();

            $conditions = $validator->validated()['conditions'] ?? [];
            $columns = $validator->validated()['columns'] ?? ['*'];

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

            // Filter only valid country fields (excluding 'columns')
            $data = array_filter($validated, function ($key) {
                return !in_array($key, ['columns', 'conditions']); // Exclude 'columns' key and conditions key
            }, ARRAY_FILTER_USE_KEY);

            // Call update function with or without columns
            $countrys = $this->countryService->updateGroup($data, $conditions, $columns);

            // Return success response.
            return ApiResponse::success($countrys, 'Country updated successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error updating country: {$e->getMessage()}", ['exception' => $e]);

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
                'id' => 'required|string',  
                'force' => 'sometimes|accepted',
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("Country updating validation failed.", [
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

            $country = $this->countryService->delete($validated['id'], $forceDelete);

            // Return success response.
            return $forceDelete ?
                ApiResponse::success($country, 'Country permenantly deleted successfully.') :
                ApiResponse::success($country, 'Country soft deleted successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error deleting country: {$e->getMessage()}", ['exception' => $e]);

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
                Log::warning("Countrys deletion validation failed.", [
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

            $deletedCountrys = $this->countryService->deleteBulk($conditions, $forceDelete);

            // Return success response.
            return $forceDelete ?
                ApiResponse::success($deletedCountrys, 'Countrys permenantly deleted successfully.') :
                ApiResponse::success($deletedCountrys, 'Countrys soft deleted successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error deleting countrys: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function isSoftDeleted(string $id)
    {
        try {
            $validator = Validator::make(['id' => $id], [
                'id' => 'required|string'
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("Country checking validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }

            $id = $validator->validated()['id'];

            $isDeleted = $this->countryService->softDeleted($id);

            return $isDeleted ?
                ApiResponse::success($isDeleted, 'Country is soft deleted') :
                ApiResponse::success($isDeleted, 'Country is not soft deleted');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error checking soft deleted country: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
    public function restore(Request $request, string $id)
    {
        try {
            // Merge request data with the provided ID for validation.
            $data = array_merge($request->all(), ['id' => $id]);
            $validator = Validator::make($data, [
                'id' => 'required|string',
                'columns'  => 'sometimes|array',
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("Country restoring validation failed.", [
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

            $country = $this->countryService->restore($id, $columns);

            return ApiResponse::success($country, 'Country is restored');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error restoring soft deleted country: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
    public function restoreBulk(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'conditions'  => 'sometimes|array',
                'columns'  => 'sometimes|array',
            ]);

            if ($validator->fails()) {
                Log::warning("Countrys restoring validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }

            $conditions = $validator->validated()['conditions'] ?? [];
            $columns = $validator->validated()['columns'] ?? ['*'];

            $countrys = $this->countryService->restoreBulk($conditions, $columns);

            return ApiResponse::success($countrys, 'Country is restored');
        } catch (Exception $e) {
            Log::error("Error restoring countrys: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }


}