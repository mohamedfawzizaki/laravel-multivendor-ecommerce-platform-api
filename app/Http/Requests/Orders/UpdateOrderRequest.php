<?php

namespace App\Http\Requests\Orders;

use Illuminate\Validation\Rule;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class UpdateOrderRequest extends FormRequest
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
            'user_id' => ['sometimes', 'uuid', 'exists:users,id'],
            'order_number' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('orders', 'order_number')->ignore($this->order),
            ],
            'subtotal' => ['sometimes', 'numeric', 'min:0'],
            'tax' => ['sometimes', 'numeric', 'min:0'],
            'total_price' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', 'in:pending,processing,shipped,delivered,cancelled,refunded'],
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