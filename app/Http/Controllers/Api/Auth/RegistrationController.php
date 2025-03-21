<?php

namespace App\Http\Controllers\Api\Auth;

use Exception;
use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreUserRequest;
use Illuminate\Database\QueryException;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Validator;
use App\Services\EmailVerificationService;

class RegistrationController extends Controller
{
    /**
     * Constructor to inject the UserService dependency.
     *
     * @param UserService $userService The user service for handling business logic related to user management.
     */
    public function __construct(public UserService $userService) {}

    /**
     * Handles user registration by creating a new user, issuing authentication tokens,
     * and sending an email verification link.
     *
     * @param StoreUserRequest $request The validated request containing user registration data.
     * @return JsonResponse JSON response indicating success or failure.
     */
    public function register(StoreUserRequest $request): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            // Create a new user using the UserService
            $user = $this->userService->create($request->validated());

            // If user creation failed, return an error response
            if (!$user) {
                Log::error('User registration failed: User creation service returned null.');
                return ApiResponse::error('User creation failed.', 400);
            }

            // Generate authentication tokens (access & refresh)
            $authService = new AuthService();
            $tokens = $authService->createTokens($request, $user, true);

            // Send email verification notification
            $emailVerificationService = new EmailVerificationService($request);
            $verificationUrl = $emailVerificationService->sendVerificationEmail($user, $tokens['access_token']);

            // Ensure the verification link was generated successfully
            if (!$verificationUrl) {
                Log::warning("Verification email failed to send to {$user->email}");
            }

            // Prepare success response with user data and tokens
            return ApiResponse::success(
                $user,
                'User registered successfully. Please check your email for verification.',
                201,
                [
                    "verification_link" => $verificationUrl,
                    "access_token" => $tokens['access_token'], // Access token
                ]
            )->cookie(
                'refresh_token', // Cookie name
                $tokens['refresh_token'], // Refresh token value
                config('api.refresh_token_expiration'), // Expiration (in minutes)
                '/', // Path (root of domain)
                null, // Domain (default)
                true, // Secure (only sent over HTTPS)
                true // HTTP-only (not accessible via JavaScript)
            );
        } catch (Exception $e) {
            // Log the exception details
            Log::error("User registration failed: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return ApiResponse::error('An unexpected error occurred during registration.', 500);
        }
    }

    
}