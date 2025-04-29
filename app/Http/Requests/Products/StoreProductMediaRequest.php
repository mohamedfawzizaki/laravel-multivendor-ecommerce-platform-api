<?php

namespace App\Http\Requests\Products;

use Illuminate\Validation\Rule;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class StoreProductMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Implement authorization if needed (e.g. user()->can('upload', Product::class))
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'   => ['required', 'exists:products,id'],
            'variation_id' => ['nullable', 'exists:product_variations,id'],
            'type'         => ['required', 'in:image,video,document'],
            'file'         => ['required', 'file', 'max:10240'], // 10MB max
            'sort_order'   => ['nullable', 'integer'],
            'is_default'   => ['nullable', 'boolean'],
            'metadata'     => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'Media type must be one of: image, video, or document.',
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