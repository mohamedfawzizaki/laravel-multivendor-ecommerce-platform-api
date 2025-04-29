<?php

namespace App\Http\Requests\Shipping;

use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class UpdateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'carrier_id'          => 'sometimes|exists:shipping_carriers,id',
            'shipping_address_id' => 'sometimes|exists:shipping_addresses,id',
            'tracking_number'     => 'sometimes|string|max:100|unique:shipments,tracking_number,' . $this->shipment,
            'shipping_cost'       => 'sometimes|numeric|min:0',
            'insurance_cost'      => 'sometimes|numeric|min:0',
            'package_weight'      => 'nullable|numeric|min:0',
            'service_level'       => 'nullable|string|max:50',
            'status'              => 'sometimes|in:label_created,pending,in_transit,out_for_delivery,delivered,exception,returned,cancelled',
            'estimated_delivery_date' => 'nullable|date',
            'label_created_at'    => 'nullable|date',
            'shipped_at'          => 'nullable|date',
            'out_for_delivery_at' => 'nullable|date',
            'delivered_at'        => 'nullable|date',
            'last_tracking_update_at' => 'nullable|date',
        ];
    }

    protected function failedValidation(ValidatorContract $validator): void
    {
        Log::warning("Validation failed for record update.", [
            'errors' => $validator->errors(),
        ]);

        throw new HttpResponseException(
            ApiResponse::error('Validation errors', 422, $validator->errors())
        );
    }
}