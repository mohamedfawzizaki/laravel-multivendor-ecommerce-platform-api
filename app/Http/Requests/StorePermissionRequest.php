<?php

namespace App\Http\Requests;

use App\Models\Permission;
use Illuminate\Support\Str;
use App\Rules\StrongPassword;
use Illuminate\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class StorePermissionRequest extends FormRequest
{
    /**
     * Indicates if the validator should stop on the first rule failure.
     * @var bool
     */
    // protected $stopOnFirstFailure = true;

    /**
     * The URI that permissions should be redirected to if validation fails.
     * this doesn't work until you comment the failedValidation method
     * @var string
     */
    // protected $redirect = '/api/permissions';

    /**
     * The named route that permissions should be redirected to if validation fails.
     * @var string
     */
    // protected $redirectRoute = 'dashboard';

    /**
     * if you plan to handle authorization logic for the request in another part of your application, 
     * you may remove the authorize method completely, or simply return true:
     * @return bool
     */
    public function authorize(): bool
    {
        // Example: Authorize only if the user has permission to create permissions
        // return auth()->user()?->can('create', Permission::class) ?? true;

        // Allow all permissions
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('slug')) {
            $this->merge([
                'slug' => Str::slug($this->slug),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|unique:permissions|max:255',
            'description' => 'required|string|unique:permissions|max:255',
        ];
    }

    /**
     * Handle failed validation for API response.
     */
    protected function failedValidation(ValidatorContract $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'errors' => $validator->errors()
        ], 422));
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            // 
        ];
    }
    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            // 
        ];
    }

    /**
     * Additional validation logic after standard validation.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->someAdditionalValidationFails()) {
                    $validator->errors()->add(
                        'extra_field',
                        'Custom validation condition failed.'
                    );
                }
            }
        ];
    }

    /**
     * Custom additional validation logic.
     */
    private function someAdditionalValidationFails(): bool
    {
        return false; // Modify as needed
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        // Example: Normalize name before processing
        // $this->replace(['name' => 'Taylor']);
        // $this->merge([
        //     'name' => trim($this->input('name'))
        // ]);
    }
}