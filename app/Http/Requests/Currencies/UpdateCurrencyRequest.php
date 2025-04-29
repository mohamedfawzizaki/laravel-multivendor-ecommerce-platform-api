<?php

namespace App\Http\Requests\Currencies;

use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class UpdateCurrencyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Update with permission logic if needed
    }

    /**
     * Validation rules for updating a currency.
     */
    public function rules(): array
    {
        return [
            'code' => 'sometimes|string|size:3|unique:currencies,code',
            'name' => 'sometimes|string|max:100',
            'symbol' => 'sometimes|string|max:10',
            'is_active' => 'sometimes|boolean',
            'is_base_currency' => 'sometimes|boolean',
            'exchange_rate' => 'sometimes|numeric|min:0.000001',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(ValidatorContract $validator)
    {
        Log::warning("Currency update failed validation.", [
            'errors' => $validator->errors(),
            'input' => $this->all(),
        ]);

        throw new HttpResponseException(
            ApiResponse::error('Validation errors', 422, $validator->errors())
        );
    }
}