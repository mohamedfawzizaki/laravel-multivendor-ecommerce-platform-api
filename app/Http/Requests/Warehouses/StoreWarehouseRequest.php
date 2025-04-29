<?php

namespace App\Http\Requests\Warehouses;

use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class StoreWarehouseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // In a real scenario, check vendor role/permission here
        return true;
    }

    /**
     * Define the validation rules for creating a warehouse.
     */
    public function rules(): array
    {
        return [
            'vendor_id'       => 'required|uuid|exists:users,id',
            'code'            => 'required|string|max:10|unique:warehouses,code',
            'name'            => 'required|string|max:100',
            'contact_name'    => 'required|string|max:100',
            'contact_email'   => 'required|email|max:100',
            'contact_phone'   => 'required|string|max:20',
            'total_capacity'  => 'required|integer|min:1',
            'city_id'         => 'required|exists:cities,id',
            'status'          => 'nullable|in:active,maintenance,retired',
            'latitude'        => 'nullable|numeric|between:-90,90',
            'longitude'       => 'nullable|numeric|between:-180,180',
            'priority'        => 'nullable|integer|min:0',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(ValidatorContract $validator): void
    {
        Log::warning("Validation failed for warehouse creation.", [
            'errors' => $validator->errors(),
        ]);

        throw new HttpResponseException(
            ApiResponse::error('Validation errors', 422, $validator->errors())
        );
    }
}