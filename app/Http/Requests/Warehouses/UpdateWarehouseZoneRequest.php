<?php

namespace App\Http\Requests\Warehouses;

use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Validation\Rule;

class UpdateWarehouseZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $zoneId = $this->route('zone')?->id ?? $this->route('id');

        return [
            'warehouse_id' => ['sometimes', 'exists:warehouses,id'],
            'code'         => [
                'sometimes', 'string', 'max:10',
                Rule::unique('warehouse_zones')->ignore($zoneId)->where(fn ($q) => $q->where('warehouse_id', $this->input('warehouse_id')))
            ],
            'name'         => ['sometimes', 'string', 'max:255'],
            'status'       => ['sometimes', Rule::in(['active', 'maintenance', 'retired'])],
        ];
    }

    protected function failedValidation(ValidatorContract $validator): void
    {
        Log::warning("Validation failed for warehouse zone updating.", [
            'errors' => $validator->errors(),
        ]);

        throw new HttpResponseException(
            ApiResponse::error('Validation errors', 422, $validator->errors())
        );
    }
}