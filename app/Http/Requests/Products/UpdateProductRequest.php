<?php

namespace App\Http\Requests\Products;

use Illuminate\Validation\Rule;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
        // return $this->user()->can('update', $this->product);
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'brand_id' => 'sometimes|exists:brands,id',
            'category_id' => 'sometimes|exists:categories,id',
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('products')
                    ->where('vendor_id', $product->vendor_id)
                    ->ignore($product->id)
                    ->whereNull('deleted_at')
            ],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('products')
                    ->where('vendor_id', $product->vendor_id)
                    ->ignore($product->id)
                    ->whereNull('deleted_at')
            ],
            'base_price' => 'nullable|numeric|min:0|decimal:0,2',
            'base_compare_price' => 'nullable|numeric|gt:base_price|decimal:0,2',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive,draft',
            'currency_code' => 'sometimes|exists:currencies,code',
            'type' => 'sometimes|in:simple,variable',
            'variations' => 'sometimes|array',
            'variations.*.id' => 'sometimes|exists:product_variations,id',
            'variations.*.variant_name' => 'sometimes|string|max:255',
            'variations.*.sku' => [
                'sometimes',
                'string',
                Rule::unique('product_variations', 'sku')
                    ->ignore($product->id, 'product_id')
            ],
            'variations.*.price' => 'sometimes|numeric|min:0|decimal:0,2',
            'variations.*.attributes' => 'nullable|array'
        ];
    }

    protected function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->type === 'simple' && empty($this->base_price)) {
                $validator->errors()->add('base_price', 'Base price is required for simple products');
            }
            
            if ($this->type === 'variable' && empty($this->variations)) {
                $validator->errors()->add('variations', 'At least one variation is required for variable products');
            }
        });
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