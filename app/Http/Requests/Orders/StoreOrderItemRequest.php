<?php

namespace App\Http\Requests;

use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class StoreOrderItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Assuming any authenticated user can create an order item.
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
            'order_id' => 'required|exists:orders,id',  // The order ID must exist in the orders table
            'product_id' => 'required|exists:products,id',  // The product ID must exist in the products table
            'variant_id' => 'nullable|exists:product_variants,id',  // Optional variant ID, must exist in product_variants table
            'quantity' => 'required|integer|min:1',  // Quantity must be a positive integer
            'price' => 'required|numeric|min:0.01',  // Price must be a numeric value greater than 0
            'subtotal' => 'required|numeric|min:0.01',  // Subtotal must be numeric and greater than 0
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
        Log::warning("Validation failed for order item creation.", [
            'errors' => $validator->errors(),
        ]);

        throw new HttpResponseException(
            ApiResponse::error('Validation errors', 422, $validator->errors())
        );
    }
}