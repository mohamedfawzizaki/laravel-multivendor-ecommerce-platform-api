<?php

namespace App\Http\Requests\Products;

use Illuminate\Validation\Rule;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class StoreProductDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => [
                'nullable',
                'exists:products,id',
                Rule::requiredIf(fn () => is_null($this->variation_id)),
            ],
            'variation_id' => [
                'nullable',
                'exists:product_variations,id',
                Rule::requiredIf(fn () => is_null($this->product_id)),
            ],
            'discount_price' => ['required_without:discount_percentage', 'nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'discount_percentage' => ['nullable', 'numeric', 'between:0,100', 'decimal:0,2'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required_if' => 'You must provide either a product or a variation for the discount.',
            'variation_id.required_if' => 'You must provide either a variation or a product for the discount.',
            'discount_price.required_without' => 'Either a discount price or percentage is required.',
            'discount_percentage.between' => 'Discount percentage must be between 0 and 100.',
            'end_date.after_or_equal' => 'End date must be the same or later than the start date.',
        ];
    }

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