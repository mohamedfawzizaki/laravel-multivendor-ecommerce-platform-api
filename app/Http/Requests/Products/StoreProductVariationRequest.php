<?php

namespace App\Http\Requests\Products;

use Illuminate\Validation\Rule;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class StoreProductVariationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'variant_name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', 'unique:product_variations,sku'],
            'price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'compare_price' => ['nullable', 'numeric', 'gt:price', 'decimal:0,2'],
            'attributes' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'compare_price.gt' => 'The compare price must be greater than the actual price.',
        ];
    }

    protected function failedValidation(ValidatorContract $validator)
    {
        Log::warning("Validation failed on variation store.", [
            'errors' => $validator->errors(),
        ]);

        throw new HttpResponseException(
            ApiResponse::error('Validation errors', 422, $validator->errors())
        );
    }
}