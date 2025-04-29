<?php

namespace App\Http\Requests\Warehouses;

use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Validation\Rule;

class StoreWarehouseShelfRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rack_id' => ['required', 'exists:warehouse_racks,id'],
            'code'    => [
                'required', 'string', 'max:10',
                Rule::unique('warehouse_shelves')->where(fn ($q) => $q->where('rack_id', $this->input('rack_id')))
            ],
            'name'    => ['required', 'string', 'max:255'],
            'status'  => ['nullable', Rule::in(['active', 'maintenance', 'retired'])],
        ];
    }

    protected function failedValidation(ValidatorContract $validator): void
    {
        Log::warning("Validation failed for warehouse shelf creation.", [
            'errors' => $validator->errors(),
        ]);

        throw new HttpResponseException(
            ApiResponse::error('Validation errors', 422, $validator->errors())
        );
    }
}