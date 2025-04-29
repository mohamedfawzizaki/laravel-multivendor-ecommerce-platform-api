<?php

namespace App\Http\Requests\Products;

use Illuminate\Validation\Rule;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;


class UpdateProductDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'discount_price' => ['sometimes', 'nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'discount_percentage' => ['sometimes', 'nullable', 'numeric', 'between:0,100', 'decimal:0,2'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'discount_percentage.between' => 'Discount percentage must be between 0 and 100.',
            'end_date.after_or_equal' => 'End date must be the same or later than the start date.',
        ];
    }

    protected function failedValidation(ValidatorContract $validator)
    {
        Log::warning("Validation failed for record update.", [
            'errors' => $validator->errors(), 
        ]);

        throw new HttpResponseException(
            ApiResponse::error('Validation errors', 422, $validator->errors())
        );
    }
}