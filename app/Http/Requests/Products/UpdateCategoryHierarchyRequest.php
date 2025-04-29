<?php

namespace App\Http\Requests\Products;

use Illuminate\Validation\Rule;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class UpdateCategoryHierarchyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryHierarchyId = $this->route('category_hierarchy'); // or passed as param

        return [
            'parent_id' => ['required', 'exists:categories,id', 'different:child_id'],
            'child_id' => [
                'required',
                'exists:categories,id',
                'different:parent_id',
                Rule::unique('category_hierarchy', 'child_id')
                    ->ignore($categoryHierarchyId)
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'parent_id.different' => 'A category cannot be its own parent.',
            'child_id.unique' => 'This child category already has a parent assigned.',
        ];
    }

    protected function failedValidation(ValidatorContract $validator)
    {
        Log::warning("Validation failed for record update.", [
            'errors' => $validator->errors(), 
        ]);

        throw new HttpResponseException(
            ApiResponse::error('Validation errors', 422, $validator->errors())
        );
    }
}