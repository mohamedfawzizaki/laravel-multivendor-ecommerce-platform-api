<?php

namespace App\Http\Requests\ProductsWarehousesManagement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Add policy checks if needed
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => 'sometimes|required|exists:warehouses,id',
            'bin_id' => 'nullable|exists:warehouse_bins,id',
            'product_id' => 'sometimes|required|exists:products,id',
            'variation_id' => 'nullable|exists:product_variations,id',
            'quantity' => 'sometimes|required|integer|min:0',
            'batch_number' => 'nullable|string|max:255',
            'expiry_date' => 'nullable|date',
            'movement_type' => 'required|in:adjustment,transfer_in,transfer_out,found,lost',
            'notes' => 'nullable|string',
        ];
    }
}