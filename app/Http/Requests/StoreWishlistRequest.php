<?php

namespace App\Http\Requests;

use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class StoreWishlistRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // You can update this based on specific authorization logic.
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'wishlist_name' => 'required|string|max:100', // Wishlist name should be a string with a max length of 100
            'product_id' => 'required|exists:products,id', // Product must exist in the products table
            'variant_id' => 'required|exists:product_variants,id',
            'notes' => 'nullable|string|max:500', // Optional notes with a max length of 500 characters
            'notify_preferences' => 'nullable|in:none,discount,restock,both', // Enum-like field for notification preferences
        ];
    }

    /**
     * Custom validation failure response.
     */
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