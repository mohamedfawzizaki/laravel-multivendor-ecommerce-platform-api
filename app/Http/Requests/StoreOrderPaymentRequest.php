<?php

namespace App\Http\Requests;

use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class StoreOrderPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Assuming any authenticated user can make a payment request.
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
            'order_id' => 'required|exists:orders,id',  // Ensure the order ID exists in the orders table
            'payment_method' => 'required|in:credit_card,paypal,stripe,bank_transfer,cash',  // Valid payment methods
            'amount' => 'required|numeric|min:0.01',  // Payment amount must be a positive number
            'currency_code' => 'required|exists:currencies,code',  // Ensure the currency code exists in the currencies table
            'transaction_id' => 'nullable|string|max:255',  // Optional but can be a string up to 255 characters
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
        Log::warning("Validation failed for order payment creation.", [
            'errors' => $validator->errors(),
        ]);

        throw new HttpResponseException(
            ApiResponse::error('Validation errors', 422, $validator->errors())
        );
    }
}