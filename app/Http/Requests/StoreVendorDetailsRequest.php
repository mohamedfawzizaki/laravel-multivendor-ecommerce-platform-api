<?php

namespace App\Http\Requests;

use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class StoreVendorDetailsRequest extends FormRequest
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
            'user_id' => 'required|string|uuid|exists:users,id',
            'documentation' => 'required|file|mimes:pdf,jpg,png|max:5120', // 5MB max
            'business_name' => 'required|string|max:255', // Made required
            'business_description' => 'nullable|string|max:1000',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // 2MB max
        ];
    }

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