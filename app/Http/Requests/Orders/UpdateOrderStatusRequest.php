<?php

namespace App\Http\Requests\Orders;

use Illuminate\Validation\Rule;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class UpdateOrderStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Assuming any authenticated user can create an order
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
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled,refunded',
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
        Log::warning("Validation failed for order update.", [
            'errors' => $validator->errors(),
        ]);

        throw new HttpResponseException(
            ApiResponse::error('Validation errors', 422, $validator->errors())
        );
    }
}