<?php

namespace App\Http\Requests\ProductsWarehousesManagement;

use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class StoreStockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Add authorization logic here if needed
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => 'required|exists:warehouses,id',
            'bin_id' => 'nullable|exists:warehouse_bins,id',
            'product_id' => 'required|exists:products,id',
            'variation_id' => 'nullable|exists:product_variations,id',
            'quantity' => 'required|integer|min:1',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'batch_number' => 'nullable|string|max:255',
            'expiry_date' => 'nullable|date',
            'movement_type' => 'required|in:purchase,adjustment,transfer_in,found',
            'notes' => 'nullable|string',
        ];
    }
    
    protected function failedValidation(ValidatorContract $validator): void
    {
        Log::warning("Validation failed for records creation.", [
            'errors' => $validator->errors(),
        ]);

        throw new HttpResponseException(
            ApiResponse::error('Validation errors', 422, $validator->errors())
        );
    }
}