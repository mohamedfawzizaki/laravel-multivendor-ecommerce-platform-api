<?php

namespace App\Http\Requests\Products;

use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class UpdateProductReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'review' => ['sometimes', 'string', 'max:5000'],
            'rating' => ['sometimes', 'integer', 'between:1,5'],
            'verified_purchase' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.between' => 'Rating must be between 1 and 5.',
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