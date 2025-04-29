<?php

namespace App\Http\Requests\ProductsWarehousesManagement;

use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class StoreStockOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Add authorization logic here if needed
    }

    public function rules(): array
    {
        return [
            'warehouse_id'   => 'required|exists:warehouses,id',
            'product_id'     => 'required|exists:products,id',
            'variation_id'   => 'nullable|exists:product_variations,id',
            'movement_type'  => 'required|in:sale,return,adjustment,transfer_out,loss',
            'quantity'       => 'required|integer|min:1',
            'notes'          => 'nullable|string',
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