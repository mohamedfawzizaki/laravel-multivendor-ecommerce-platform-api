<?php

namespace App\Http\Requests\CartAndWishlist;

use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class StoreCartRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            // 'user_id' => ['nullable', 'uuid', 'exists:users,id'],
            // 'session_id' => ['nullable', 'uuid'],

            'product_id' => ['required', 'exists:products,id'],
            'variation_id' => ['nullable', 'exists:product_variations,id'],

            'quantity' => ['required', 'integer', 'min:1', 'max:100'],

            'notes' => ['nullable', 'string', 'max:500'],

            'expires_at' => ['nullable', 'date'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!Auth::user()->id && !session('session_id')) {
                $validator->errors()->add('session_id', 'Either user_id or session_id must be provided.');
            }
        });
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