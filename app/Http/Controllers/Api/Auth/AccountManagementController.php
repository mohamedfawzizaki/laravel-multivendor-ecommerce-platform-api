<?php

namespace App\Http\Controllers\Api\Auth;

use Exception;
use App\Models\Status;
use Illuminate\Http\Request;
use App\Rules\StrongPassword;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Validator;
use App\Notifications\AccountDeactivatedNotification;

class AccountManagementController extends Controller
{
    public function __construct(public AuthService $authService) {}




    public function changeUsername(Request $request): JsonResponse
    {
        // Ensure user is authenticated
        if (!Auth::check()) {
            return ApiResponse::error('Unauthorized', 401);
        }

        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:users,name', // New name must be valid & unique
            'reauth_token' => 'required|string', // Re-authentication token is required
        ]);

        // Handle validation failure
        if ($validator->fails()) {
            return ApiResponse::error('Validation Failed', 422, $validator->errors());
        }

        // Verify the re-authentication token
        $tokenModel = PersonalAccessToken::findToken($request->reauth_token);
        if (!$tokenModel || $tokenModel->name !== 'reauth_token' || $tokenModel->expires_at->isPast()) {
            Log::warning('Invalid or expired re-authentication token', [
                'ip' => $request->ip(),
                'user_id' => Auth::id(),
            ]);

            return ApiResponse::error('Reauthentication token is invalid or expired.', 403);
        }

        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $previous_name = $user->name;

            // Get the validated name safely
            $validatedUsername = $validator->validated()['name'];

            // Prevent updating with the same name
            if ($previous_name === $validatedUsername) {
                return ApiResponse::error('New name must be different from the current name.', 400);
            }

            // Update the user's name in the database
            $user->update(['name' => $validatedUsername]);

            // Revoke the used re-authentication token
            $tokenModel->delete();

            // Log the name change event
            Log::info('User changed name', [
                'user_id' => $user->id,
                'previous_name' => $previous_name,
                'new_name' => $validatedUsername,
                'ip' => $request->ip(),
            ]);

            return $this->authService->logout($request, 'name changed successfully, please login again');
        } catch (QueryException $e) {
            Log::error('Database error while updating name', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return ApiResponse::error('Failed to update name', 500, ['error' => 'A database error occurred.']);
        } catch (Exception $e) {
            Log::error('Unexpected error while changing name', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return ApiResponse::error('Failed to update name', 500, ['error' => 'An unexpected error occurred.']);
        }
    }
    /**
     * Change the authenticated user's email address.
     *
     * This method allows a user to update their email address after successful 
     * re-authentication using a short-lived token.
     *
     * @param Request $request The HTTP request containing the new email and re-authentication token.
     * 
     * @return JsonResponse The API response containing success or error messages.
     *
     * @throws QueryException If a database-related error occurs.
     * @throws Exception If an unexpected error occurs.
     */
    public function changeEmail(Request $request): JsonResponse
    {
        // Ensure user is authenticated
        if (!Auth::check()) {
            return ApiResponse::error('Unauthorized', 401);
        }

        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email', // New email must be valid & unique
            'reauth_token' => 'required|string', // Re-authentication token is required
        ]);

        // Handle validation failure
        if ($validator->fails()) {
            return ApiResponse::error('Validation Failed', 422, $validator->errors());
        }

        // Verify the re-authentication token
        $tokenModel = PersonalAccessToken::findToken($request->reauth_token);
        if (!$tokenModel || $tokenModel->name !== 'reauth_token' || $tokenModel->expires_at->isPast()) {
            Log::warning('Invalid or expired re-authentication token', [
                'ip' => $request->ip(),
                'user_id' => Auth::id(),
            ]);

            return ApiResponse::error('Reauthentication token is invalid or expired.', 403);
        }

        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $previous_email = $user->email;

            // Get the validated email safely
            $validatedEmail = $validator->validated()['email'];

            // Prevent updating with the same email
            if ($previous_email === $validatedEmail) {
                return ApiResponse::error('New email must be different from the current email.', 400);
            }

            // Update the user's email in the database
            $user->update(['email' => $validatedEmail]);

            // Revoke the used re-authentication token
            $tokenModel->delete();

            // Log the email change event
            Log::info('User changed email', [
                'user_id' => $user->id,
                'previous_email' => $previous_email,
                'new_email' => $validatedEmail,
                'ip' => $request->ip(),
            ]);

            return $this->authService->logout($request, 'Email changed successfully, please login again');
        } catch (QueryException $e) {
            Log::error('Database error while updating email', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return ApiResponse::error('Failed to update email', 500, ['error' => 'A database error occurred.']);
        } catch (Exception $e) {
            Log::error('Unexpected error while changing email', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return ApiResponse::error('Failed to update email', 500, ['error' => 'An unexpected error occurred.']);
        }
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => ['required', new StrongPassword],
            'new_password' => ['required', 'confirmed',  new StrongPassword]
        ]);

        // Handle validation failure
        if ($validator->fails()) {
            return ApiResponse::error('Validation Failed', 422, $validator->errors());
        }
        try {

            /** @var \App\Models\User $user */
            // Retrieve the currently authenticated user
            $user = Auth::user();

            // Verify if the provided password matches the stored hashed password
            if (!Hash::check($request->old_password, $user->password)) {
                return ApiResponse::error('Invalid old password.', 403);
            }

            // Update the user's password
            $user->update(['password' => $validator->validated()['new_password']]);

            return $this->authService->logout($request, 'password changed successfully, please login again');
        } catch (QueryException $e) {
            Log::error('Database error while updating password', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return ApiResponse::error('Failed to update password', 500, ['error' => 'A database error occurred.']);
        } catch (Exception $e) {
            Log::error('Unexpected error while changing password', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return ApiResponse::error('Failed to update password', 500, ['error' => 'An unexpected error occurred.']);
        }
    }

    public function deactivateAccount()
    {
        /**
         * @var \App\Models\User
         */
        $user = Auth::user();

        $statusId = Status::where('name', 'inactive')->first()->id;

        if ($user->status_id == $statusId) {
            return ApiResponse::success([], 'account is already inactive');
        }

        $user->status_id = $statusId;

        if ($user->save()) {
            // Revoke all tokens (force logout)
            $user->tokens()->delete();
            // Send notification
            $user->notify(new AccountDeactivatedNotification());

            return ApiResponse::success([], 'account is inactive now');
        }
        return ApiResponse::error('error while deactivating your account');
    }
}