<?php

namespace App\Http\Requests\Currencies;

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
        // Customize this as per role/permission later if needed
        return true;
    }

    /**
     * Validation rules for storing a currency.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => 'required|string|size:3|unique:currencies,code', // ISO code, e.g., USD
            'name' => 'required|string|max:100',
            'symbol' => 'required|string|max:10',
            'is_active' => 'boolean',
            'is_base_currency' => 'boolean',
            'exchange_rate' => 'required|numeric|min:0.000001', // Cannot be zero or negative
        ];
    }

    /**
     * Custom error message formatting and logging.
     */
    protected function failedValidation(ValidatorContract $validator)
    {
        Log::warning("Currency creation failed validation.", [
            'errors' => $validator->errors(),
            'input' => $this->all(),
        ]);

        throw new HttpResponseException(
            ApiResponse::error('Validation errors', 422, $validator->errors())
        );
    }
}