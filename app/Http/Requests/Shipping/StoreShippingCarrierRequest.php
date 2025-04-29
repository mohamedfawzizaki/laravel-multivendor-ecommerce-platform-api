<?php

namespace App\Http\Requests\Shipping;

use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class StoreShippingCarrierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vendor_id'                => 'nullable|exists:users,id',
            'code'                     => 'required|string|max:10|unique:shipping_carriers,code',
            'name'                     => 'required|string|max:100|unique:shipping_carriers,name',
            'tracking_url_format'      => 'nullable|url',
            'customer_service_phone'   => 'nullable|string|max:20',
            'customer_service_email'   => 'nullable|email',
            'website_url'              => 'nullable|url',
            'is_active'                => 'sometimes|boolean',
            'service_levels'           => 'nullable|array',
        ];
    }

    protected function failedValidation(ValidatorContract $validator): void
    {
        Log::warning("Validation failed for record creation.", [
            'errors' => $validator->errors(),
        ]);

        throw new HttpResponseException(
            ApiResponse::error('Validation errors', 422, $validator->errors())
        );
    }
}