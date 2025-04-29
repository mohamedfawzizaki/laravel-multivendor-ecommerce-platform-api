<?php

namespace App\Http\Requests\Warehouses;

use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Validation\Rule;

class UpdateWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        // In a real app, check if the authenticated user is authorized to update the warehouse
        return true;
    }

    public function rules(): array
    {
        return [
            'vendor_id'       => 'sometimes|uuid|exists:users,id',
            'code'            => 'sometimes|string|max:10|unique:warehouses,code',
            'name'            => 'sometimes|string|max:100',
            'contact_name'    => 'sometimes|string|max:100',
            'contact_email'   => 'sometimes|email|max:100',
            'contact_phone'   => 'sometimes|string|max:20',
            'total_capacity'  => 'sometimes|integer|min:1',
            'city_id'         => 'sometimes|exists:cities,id',
            'status'          => 'sometimes|in:active,maintenance,retired',
            'latitude'        => 'nullable|numeric|between:-90,90',
            'longitude'       => 'nullable|numeric|between:-180,180',
            'priority'        => 'sometimes|integer|min:0',
        ];
    }

    protected function failedValidation(ValidatorContract $validator): void
    {
        Log::warning("Validation failed for warehouse updating.", [
            'errors' => $validator->errors(),
        ]);

        throw new HttpResponseException(
            ApiResponse::error('Validation errors', 422, $validator->errors())
        );
    }
}