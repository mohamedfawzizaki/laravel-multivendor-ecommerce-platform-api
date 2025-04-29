<?php

namespace App\Http\Requests\Warehouses;

use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Validation\Rule;

class StoreWarehouseZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'code'         => [
                'required', 'string', 'max:10',
                Rule::unique('warehouse_zones')->where(fn ($q) => $q->where('warehouse_id', $this->input('warehouse_id')))
            ],
            'name'         => ['required', 'string', 'max:255'],
            'status'       => ['nullable', Rule::in(['active', 'maintenance', 'retired'])],
        ];
    }

    protected function failedValidation(ValidatorContract $validator): void
    {
        Log::warning("Validation failed for warehouse zone creation.", [
            'errors' => $validator->errors(),
        ]);

        throw new HttpResponseException(
            ApiResponse::error('Validation errors', 422, $validator->errors())
        );
    }
}
 