<?php

namespace App\Http\Requests;

use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class PaginateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // 
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        return [
            // 'paginate' is optional and must be accepted (true, 1, "1", "true", etc.) if provided.
            'paginate' => 'sometimes|accepted',
            
            // 'per_page' is required if 'paginate' is accepted. It must be an integer and at least 1.
            'per_page' => 'required_if:paginate,true|integer|min:1',
            
            // 'page' is required if 'paginate' is accepted. It must be an integer and at least 1.
            'page' => 'required_if:paginate,true|integer|min:1',
            
            // 'columns' is optional and must be an array if provided.
            'columns' => 'sometimes|array',
            
            // // Validate each item in the 'columns' array (optional but recommended).
            // instead validate in the base repository
            // 'columns.*' => 'string|in:id,name,email,created_at', // Ensure valid column names.
            
            // 'pageName' is optional and must be a string if provided.
            'pageName' => 'sometimes|string',
            
            'with_trashed' => 'sometimes|accepted',
            'only_trashed' => 'sometimes|accepted',
            'conditions'   => 'sometimes|array',
        ];
    }

    /**
     * Handle failed validation for API response.
     */
    protected function failedValidation(ValidatorContract $validator)
    {
        // Log a warning with details about the validation failure.
        Log::warning("Validation failed for record retrieval.", [
            'errors' => $validator->errors(), // The validation error messages.
        ]);
        
        throw new HttpResponseException(
            // Return a structured error response using a custom `ApiResponse` class.
            ApiResponse::error(
                'Invalid request parameters.', // User-friendly error message.
                422, // HTTP status code for unprocessable entity.
                $validator->errors() // Validation errors to provide feedback to the client.
            )
        );
    }
}