<?php

namespace App\Http\Requests\Products;

use Illuminate\Validation\Rule;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;


class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        // return $this->user()->can('create', Product::class);
        return true;
    }

    public function rules(): array
    {
        return [
            // 'vendor_id' => 'required|exists:users,id',
            'brand_id' => 'required|exists:brands,id',
            'category_id' => 'required|exists:categories,id',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products')
                    // ->where('vendor_id', $this->vendor_id)
                    ->where('vendor_id', $this->user()->id)
                    ->whereNull('deleted_at')
            ],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('products')
                    ->where('vendor_id', $this->user()->id)
                    ->whereNull('deleted_at')
            ],
            'base_price' => 'nullable|numeric|min:0|decimal:0,2',
            'base_compare_price' => 'nullable|numeric|gt:base_price|decimal:0,2',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive,draft',
            'currency_code' => 'required|exists:currencies,code',
            'type' => 'required|in:simple,variable',
            // 'variations' => 'required_if:type,variable|array',
            // 'variations.*.variant_name' => 'required_if:type,variable|string|max:255',
            // 'variations.*.sku' => 'required_if:type,variable|string|unique:product_variations,sku',
            // 'variations.*.price' => 'required_if:type,variable|numeric|min:0|decimal:0,2',
            // 'variations.*.attributes' => 'nullable|array'
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'Slug can only contain lowercase letters, numbers and hyphens',
            'base_compare_price.gt' => 'Compare price must be greater than base price',
            'variations.required_if' => 'Variations are required for variable products',
            'variations.*.sku.unique' => 'The SKU ":input" has already been taken'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'slug' => strtolower($this->slug),
            'base_price' => $this->type === 'simple' ? $this->base_price : null,
            'base_compare_price' => $this->type === 'simple' ? $this->base_compare_price : null
        ]);
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

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        
        // Example: Normalize name before processing
        // $this->replace(['name' => 'Taylor']);
    //     $this->merge([
    //         'name' => trim($this->input('name')),
    //         'email' => trim($this->input('email')),
    //         'password' => trim($this->input('password')),
    //     ]);
    }
}