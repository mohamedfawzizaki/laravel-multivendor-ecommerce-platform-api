<?php

namespace App\Http\Requests\Orders;

use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class UpdateTaxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'rate' => 'sometimes|required|numeric|min:0|max:100',
            'is_inclusive' => 'sometimes|boolean',
            'tax_type' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:255',
            'active' => 'sometimes|boolean',
        ];
    }

    protected function failedValidation(ValidatorContract $validator)
    {
        Log::warning("Validation failed for order creation.", [
            'errors' => $validator->errors(),
        ]);

        throw new HttpResponseException(
            ApiResponse::error('Validation errors', 422, $validator->errors())
        );
    }
}