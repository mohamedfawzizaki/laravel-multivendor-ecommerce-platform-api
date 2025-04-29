<?php

namespace App\Http\Controllers\Api\Public\Orders;

use Exception;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaginateRequest;
use App\Services\Orders\CurrencyService;
use Illuminate\Support\Facades\Validator;
use App\Services\Products\ProductVariantService;
use App\Http\Requests\ValidateColumnAndConditionRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CurrencyController extends Controller
{
    /**
     * Constructor to inject the CurrencyService dependency.
     *
     * @param CurrencyService $currencyService The service responsible for currency-related operations.
     */
    public function __construct(protected CurrencyService $currencyService, protected ProductVariantService $productVariantService) {}

    public function index(PaginateRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $paginate     = $validated['paginate']     ?? false;
            $conditions   = $validated['conditions']   ?? [];
            $columns      = $validated['columns']      ?? ['*'];
            $perPage      = $validated['per_page']     ?? 15;
            $pageName     = $validated['pageName']     ?? 'page';
            $page         = $validated['page']         ?? 1;

            $currencies = $paginate
                ? $this->currencyService->getAllCurrencies(
                    perPage: $perPage,
                    columns: $columns,
                    pageName: $pageName,
                    page: $page,
                    conditions: $conditions
                )
                : $this->currencyService->getAllCurrencies(
                    columns: $columns,
                    conditions: $conditions
                );

            return ApiResponse::success($currencies, 'Currencies retrieved successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function show(ValidateColumnAndConditionRequest $request, string $code): JsonResponse
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];


            $currency = $this->currencyService->searchBy('code', strtolower($code), $columns);

            if (!$currency) {
                return ApiResponse::error('Currency not found.', 404);
            }

            return ApiResponse::success($currency, 'Currency retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving currency: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Search for currencies based on dynamic conditions.
     *
     * @param ValidateColumnAndConditionRequest $request
     * @param string $name
     * @return JsonResponse
     */
    public function search(string $query): JsonResponse
    {
        try {
            // Validate the incoming request
            $validator = Validator::make(['query'=>$query], [
                'query' => 'required|string', // The search value must be a string
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Invalid request parameters.', 400);
            }
            // Validate the incoming request
            $value = $validator->validated()['query'];

            $result = Currency::whereAny(['code', 'name', 'symbol'], 'like', '%' . $value . '%')->get();

            // Check if the result is empty
            if ($result->isEmpty()) {
                return ApiResponse::error('No matching currencies found.', 404);
            }

            return ApiResponse::success($result, 'Currencies found.');
        } catch (ModelNotFoundException $e) {
            Log::warning("Currency search failed: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error('Currency not found.', 404);
        } catch (Exception $e) {
            Log::error("Error searching currencies: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error('An error occurred while searching for currencies.', 500);
        }
    }
}