<?php

namespace App\Http\Requests;

use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class StoreCurrencyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Assuming any authenticated user can perform this action.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => 'required|string|size:3|unique:currencies,code',  // ISO currency code (e.g., 'USD')
            'name' => 'required|string|max:100',  // Full currency name (e.g., 'United States Dollar')
            'symbol' => 'required|string|max:10',  // Currency symbol (e.g., '$', '€')
            'exchange_rate' => 'required|numeric|min:0.000001',  // Exchange rate (must be a positive number)
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param ValidatorContract $validator
     * @return void
     * @throws HttpResponseException
     */
    protected function failedValidation(ValidatorContract $validator)
    {
        Log::warning("Validation failed for record creation.", [
            'errors' => $validator->errors(),
        ]);

        throw new HttpResponseException(
            ApiResponse::error('Validation errors', 422, $validator->errors())
        );
    }
}