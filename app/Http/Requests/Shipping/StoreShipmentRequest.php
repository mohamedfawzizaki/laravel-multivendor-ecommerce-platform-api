<?php

namespace App\Http\Requests\Shipping;

use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class StoreShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vendor_order_id'     => 'required|exists:vendor_orders,id',
            'carrier_id'          => 'required|exists:shipping_carriers,id',
            'shipping_cost'       => 'required|numeric|min:0',
            'insurance_cost'      => 'nullable|numeric|min:0',
            'package_weight'      => 'nullable|numeric|min:0',
            'service_level'       => 'nullable|string|max:50',
            'estimated_delivery_date' => 'nullable|date',
            'out_for_delivery_at' => 'nullable|date',
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