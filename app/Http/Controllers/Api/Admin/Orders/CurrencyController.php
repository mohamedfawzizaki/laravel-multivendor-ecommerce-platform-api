<?php

namespace App\Http\Controllers\Api\Admin\Orders;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\Orders\CurrencyService;
use App\Http\Requests\Currencies\StoreCurrencyRequest;
use App\Http\Requests\Currencies\UpdateCurrencyRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CurrencyController extends Controller
{
    /**
     * Constructor to inject the CurrencyService dependency.
     *
     * @param CurrencyService $currencyService The service responsible for currency-related operations.
     */
    public function __construct(
        protected CurrencyService $currencyService,
    ) {}

    /**
     * Store a new currency.
     */
    public function store(StoreCurrencyRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            // Ensure the currency code is uppercase
            $validated['code'] = strtoupper($validated['code']);
            
            // Create the currency
            $currency = $this->currencyService->create($validated);
            
            return ApiResponse::success($currency, 'Currency created successfully.');
        } catch (Exception $e) {
            Log::error("Error creating currency: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error('An error occurred while creating the currency.', 500);
        }
    }
    
    /**
     * Update an existing currency.
     */
    public function update(UpdateCurrencyRequest $request, string $code): JsonResponse
    {
        try {
            // Find the existing currency
            $currency = $this->currencyService->searchBy('code', $code);
            
            if (!$currency) {
                return ApiResponse::error('Currency not found.', 404);
            }
            
            // Validate and update the currency
            $validated = $request->validated();
            
            if (isset($validated['code'])) {
                $validated['code'] = strtoupper($validated['code']);
            }
                
            $currency->update($validated);

            return ApiResponse::success($currency, 'Currency updated successfully.');
        } catch (ModelNotFoundException $e) {
            Log::warning("Currency with code {$code} not found.", ['exception' => $e]);
            return ApiResponse::error('Currency not found.', 404);
        } catch (Exception $e) {
            Log::error("Error updating currency: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error('An error occurred while updating the currency.', 500);
        }
    }

    /**
     * Delete a currency by its code.
     */
    public function delete(string $code): JsonResponse
    {
        try {
            // Find the currency
            $currency = $this->currencyService->searchBy('code', $code);

            if (!$currency) {
                return ApiResponse::error('Currency not found.', 404);
            }

            // Delete the currency
            $currency->delete();
            
            return ApiResponse::success([], 'Currency deleted successfully.');
        } catch (ModelNotFoundException $e) {
            Log::warning("Currency with code {$code} not found for deletion.", ['exception' => $e]);
            return ApiResponse::error('Currency not found.', 404);
        } catch (Exception $e) {
            Log::error("Error deleting currency: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error('An error occurred while deleting the currency.', 500);
        }
    }
}