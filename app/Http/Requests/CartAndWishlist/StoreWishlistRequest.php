<?php

namespace App\Http\Requests\CartAndWishlist;

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
            // 'user_id' => ['nullable', 'uuid', 'exists:users,id'],
            // 'session_id' => ['nullable', 'uuid'],
            'wishlist_name' => ['sometimes', 'string', 'max:100'],
            'product_id' => ['required', 'exists:products,id'],
            'variation_id' => ['nullable', 'exists:product_variations,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'notify_preferences' => ['nullable', 'in:none,discount,restock,both'],
            'expires_at' => ['nullable', 'date'],
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