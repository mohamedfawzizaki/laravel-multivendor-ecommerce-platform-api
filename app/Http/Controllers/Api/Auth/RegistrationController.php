<?php

namespace App\Http\Controllers\Api\Auth;

use Exception;
use Illuminate\Http\JsonResponse;

use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\RegisterationService;
use App\Http\Requests\RegisterUserRequest;

class RegistrationController extends Controller
{
    /**
     * Constructor to inject the RegisterationService dependency.
     *
     * @param RegisterationService $registerationService
     */
    public function __construct(public RegisterationService $registerationService) {}

    public function register(RegisterUserRequest $request): JsonResponse
    {

        try {
            $result = $this->registerationService->registerUser($request);

            return ApiResponse::success(
                $result['user'],
                'User registered successfully. Please check your email for verification.',
                201,
                [
                    "verification_link" => $result['verification_url'],
                    "access_token" => $result['tokens']['access_token'], // Access token
                ]
            )->cookie(
                'access_token', // Cookie name
                $result['tokens']['access_token'], // Token value
                config('api.access_token_expiration'), // Expiration (in minutes)
                '/', // Path
                null, // Domain (null = default)
                true, // Secure (HTTPS only)
                true // HTTP-only
            )->cookie(
                'refresh_token', // Cookie name
                $result['tokens']['refresh_token'], // Refresh token value
                config('api.refresh_token_expiration'), // Expiration (in minutes)
                '/', // Path (root of domain)
                null, // Domain (default)
                true, // Secure (only sent over HTTPS)
                true // HTTP-only (not accessible via JavaScript)
            );
        } catch (Exception $e) {
            Log::error("User registration failed: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return ApiResponse::error('An unexpected error occurred during registration.', 500);
        }
    }
}