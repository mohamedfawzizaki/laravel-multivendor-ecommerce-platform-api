<?php

namespace App\Http\Requests\Warehouses;

use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Validation\Rule;

class StoreWarehouseBinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shelf_id'   => ['required', 'exists:warehouse_shelves,id'],
            'code'       => [
                'required',
                'string',
                'max:10',
                Rule::unique('warehouse_bins')->where(fn($q) => $q->where('shelf_id', $this->input('shelf_id')))
            ],
            'name'       => ['required', 'string', 'max:255'],
            'bin_type'   => ['required', Rule::in(['small', 'medium', 'large', 'pallet', 'bulk'])],
            'width'      => ['nullable', 'numeric', 'min:0'],
            'height'     => ['nullable', 'numeric', 'min:0'],
            'depth'      => ['nullable', 'numeric', 'min:0'],
            'max_weight' => ['nullable', 'numeric', 'min:0'],
            'status'     => ['nullable', Rule::in(['active', 'maintenance', 'retired'])],
        ];
    }

    protected function failedValidation(ValidatorContract $validator): void
    {
        Log::warning("Validation failed for warehouse bin creation.", [
            'errors' => $validator->errors(),
        ]);

        throw new HttpResponseException(
            ApiResponse::error('Validation errors', 422, $validator->errors())
        );
    }
}