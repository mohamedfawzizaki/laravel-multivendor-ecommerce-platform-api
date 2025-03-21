<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Rules\StrongPassword;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Auth\Authenticatable;

class AuthService
{
    /**
     * Validate user login credentials.
     *
     * This method checks if either an email or name is provided along with a strong password.
     * It ensures validation security by returning a generic error response without exposing specific errors.
     *
     * @param \Illuminate\Http\Request $request The HTTP request containing login credentials.
     * @return bool Returns true if validation passes, otherwise false.
     */
    public function validateLoginCredentials(Request $request): bool
    {
        // Define validation rules for login credentials
        $rules = [
            'email' => 'required_without:name|email', // Email is required if name is not provided and must be a valid email
            'name' => 'required_without:email|string', // Username is required if email is not provided and must be a string
            'password' => ['required', new StrongPassword()], // Password is required and must meet strong password criteria
        ];

        // Validate only the required input fields to optimize performance
        $validator = Validator::make($request->only(['email', 'name', 'password']), $rules);

        // If validation fails, return false to prevent detailed validation errors from being exposed
        if ($validator->fails()) {
            return false;
        }

        // Validation passed, return true
        return true;
    }

    /**
     * Authenticate a user based on email or name and implement brute-force protection.
     *
     * This method checks for too many failed login attempts, locks the account temporarily if necessary,
     * and authenticates the user with the provided credentials.
     *
     * @param \Illuminate\Http\Request $request The HTTP request containing login credentials.
     * @return \Illuminate\Contracts\Auth\Authenticatable|\Illuminate\Http\JsonResponse
     *         Returns the authenticated user on success, or a JSON error response on failure.
     */
    public function authUser(Request $request): Authenticatable|JsonResponse
    {
        // Determine the login field dynamically based on the provided input (email or name)
        $loginField = $request->has('email') ? 'email' : 'name';

        // Prepare credentials array for authentication
        $credentials = [
            $loginField => $request->input($loginField), // Use email or name dynamically
            'password'  => $request->input('password'), // Extract password from the request
        ];

        // Define cache keys for tracking failed login attempts and account lockout
        $lockoutKey = 'account_lockout:' . ($request->email ?? $request->name);
        $attemptsKey = 'failed_attempts:' . ($request->email ?? $request->name);

        // Check if the account is temporarily locked due to excessive failed login attempts
        if (Cache::has($lockoutKey)) {
            // Log the lockout event for security monitoring
            Log::warning('Account temporarily locked due to too many failed attempts', [
                'email' => $request->input('email'),
                'name' => $request->input('name'),
                'ip' => $request->ip(),
            ]);

            // Return an error response indicating account lockout
            return ApiResponse::error('Account temporarily locked. Please try again later.', 429, ['too many attempts']);
        }

        // Attempt to authenticate the user with the provided credentials
        if (!Auth::attempt($credentials)) {
            // Log the failed login attempt for security analysis
            Log::warning('Failed login attempt', [
                'email' => $request->input('email'),
                'name' => $request->input('name'),
                'ip' => $request->ip(),
            ]);

            // Increment the failed login attempt counter
            $failedAttempts = Cache::get($attemptsKey, 0) + 1;
            Cache::put($attemptsKey, $failedAttempts, 300); // Store count for 5 minutes (300 seconds)

            // If failed attempts reach 5, lock the account for 5 minutes
            if ($failedAttempts >= 5) {
                Cache::put($lockoutKey, true, 300); // Set lockout flag in cache for 5 minutes
                Cache::forget($attemptsKey); // Reset failed attempts after lockout
            }

            // Return an error response for invalid credentials
            return ApiResponse::error('Invalid credentials', 401);
        }

        // Reset failed attempts and lockout status on successful authentication
        Cache::forget($attemptsKey);
        Cache::forget($lockoutKey);

        // Return the authenticated user
        return Auth::user();
    }

    public function createTokens(Request $request, $user, bool $fingerprint = false, ?string $tokenName = 'access_and_refresh_tokens'): array|string
    {
        // Set expiration times for different tokens based on configuration settings
        $accessTokenExpiresAt  = Carbon::now()->addMinutes(config('sanctum.expiration') ?? config('api.access_token_expiration'));
        // Access token expiration (default: 60 min)

        $refreshTokenExpiresAt = Carbon::now()->addMinutes(config('api.refresh_token_expiration'));
        // Refresh token expiration (default: 1 month)

        $reuAuthTokenExpiresAt = Carbon::now()->addMinutes(config('api.reAuth_token_expiration'));
        // Re-authentication token expiration (default: as configured)

        // Check if fingerprint-based authentication is disabled
        if (!$fingerprint) {
            // If generating a re-authentication token, return only that token
            if ($tokenName == 'reauth_token') {
                return $this->createTokenHelper($user, $tokenName, ['*'], $reuAuthTokenExpiresAt);
            } elseif ($tokenName == 'access_token') {
                return $this->createTokenHelper($user, $tokenName, ['*'], $reuAuthTokenExpiresAt);
            } else {
                // Generate access and refresh tokens without fingerprint
                $accessToken = $this->createTokenHelper($user, 'access_token', ['*'], $accessTokenExpiresAt);
                $refreshToken = $this->createTokenHelper($user, 'refresh_token', ['refresh'], $refreshTokenExpiresAt);
            }
        } else {
            // Generate a fingerprint based on the user's IP address and user agent for additional security
            // $currentFingerprint = hash('sha256', $request->ip() . $request->userAgent());
            // $currentFingerprint = Crypt::encryptString($request->ip() . $request->userAgent());
            $currentFingerprint = $request->ip() . '|' . $request->userAgent();

            // If generating a re-authentication token, return only that token
            if ($tokenName == 'reauth_token') {
                return $this->createTokenHelper($user, $tokenName, ['*'], $reuAuthTokenExpiresAt, $currentFingerprint);
            } elseif ($tokenName == 'access_token') {
                return $this->createTokenHelper($user, $tokenName, ['*'], $reuAuthTokenExpiresAt, $currentFingerprint);
            } else {
                // Generate access and refresh tokens without fingerprint
                $accessToken = $this->createTokenHelper($user, 'access_token', ['*'], $accessTokenExpiresAt, $currentFingerprint);
                $refreshToken = $this->createTokenHelper($user, 'refresh_token', ['refresh'], $refreshTokenExpiresAt, $currentFingerprint);
            }
        }

        // Return the generated tokens  
        return [
            'access_token'=>$accessToken,
            'refresh_token'=>$refreshToken,
        ];
    }

    private function createTokenHelper($user, string $tokenName, $abilities = ['*'], $expiration, $fingerprint = null): string
    {
        // If no fingerprint is provided, create a basic token
        if (!$fingerprint) {
            return $user->createToken($tokenName, $abilities, $expiration)->plainTextToken;
        }

        // Create a token instance with fingerprint binding
        $tokenInstance = $user->createToken($tokenName, $abilities, $expiration);
        $tokenInstance->accessToken->fingerprint = $fingerprint; // Associate fingerprint
        $tokenInstance->accessToken->save(); // Persist the fingerprint association

        return $tokenInstance->plainTextToken;
    }

    public function logout(Request $request, $message = '', $metadata = [])
    {
        // Ensure the user is authenticated
        if (!$request->user()) {
            return ApiResponse::error('Unauthorized', 401);
        }

        // Get the authenticated user
        $user = $request->user();

        // Revoke the current access token
        $user->currentAccessToken()->delete();

        // Get the current refresh token from the cookie
        $refreshToken = $request->cookie('refresh_token');

        // Find and delete the refresh token from the database
        if ($refreshToken) {
            $refreshTokenModel = PersonalAccessToken::findToken($refreshToken);
            if ($refreshTokenModel) {
                $refreshTokenModel->delete();
            }
        }

        // Log the action
        Log::info('User logged out', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
        ]);

        // Clear the refresh token cookie
        return ApiResponse::success($user, $message, 200)
            ->withoutCookie('refresh_token');
    }
}