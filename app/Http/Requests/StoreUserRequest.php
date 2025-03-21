<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Support\Str;
use App\Rules\StrongPassword;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class StoreUserRequest extends FormRequest
{
    /**
     * Indicates if the validator should stop on the first rule failure.
     * @var bool
     */
    // protected $stopOnFirstFailure = true;

    /**
     * The URI that users should be redirected to if validation fails.
     * this doesn't work until you comment the failedValidation method
     * @var string
     */
    // protected $redirect = '/api/users';

    /**
     * The named route that users should be redirected to if validation fails.
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
        // Example: Authorize only if the user has permission to create users
        // return auth()->user()?->can('create', User::class) ?? true;

        // Allow all users
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
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|unique:users,name|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed',  new StrongPassword],
            'role_id' => 'sometimes|exists:roles,id',
        ];
    }

    /**
     * Handle failed validation for API response.
     */
    protected function failedValidation(ValidatorContract $validator)
    {
        // Log a warning with details about the validation failure.
        Log::warning("Validation failed for user retrieval.", [
            'errors' => $validator->errors(), // The validation error messages.
        ]);

        throw new HttpResponseException(
            ApiResponse::error('Validation errors', 422, $validator->errors())
        );
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'email.unique' => 'This email is already registered.',
            'password.confirmed' => 'Passwords do not match.',
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
            'email' => 'email address',
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
        $this->merge([
            'name' => trim($this->input('name')),
            'email' => trim($this->input('email')),
            'password' => trim($this->input('password')),
        ]);
    }
}