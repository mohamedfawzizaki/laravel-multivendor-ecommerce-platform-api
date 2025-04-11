<?php

namespace App\Http\Controllers\Api\Auth;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Rules\StrongPassword;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * AuthController constructor.
     *
     * Uses constructor property promotion to inject the AuthService dependency.
     *
     * @param \App\Services\AuthService $authService Handles authentication-related operations.
     */
    public function __construct(public AuthService $authService) {}

    public function login(Request $request): JsonResponse
    {
        // update last_login_at

        if (!$this->authService->validateLoginCredentials($request)) {
            // More secure: Return a generic error message to avoid leaking information
            return ApiResponse::error('Invalid login credentials', 401);
        }

        /** @var \App\Models\User $user */
        // Get the authenticated user
        $user = $this->authService->authUser($request);

        if (!$user instanceof \App\Models\User) {
            return $user;
        }

        $tokens = $this->authService->createTokens($request, $user, true, 'access_and_refresh_tokens');
        // Log the successful login for auditing
        Log::info("User {$user->id} logged in from IP: " . $request->ip() . " with User-Agent: " . $request->header('User-Agent'));

        // Return a success response with the user data, access token, and access token expiration
        return ApiResponse::success($user, 'User Login Successfully', 200, [
            "access_token" => $tokens['access_token'], // Include the access token in the response
        ])->cookie(
            'access_token', // Cookie name
            $tokens['access_token'], // Token value
            config('api.access_token_expiration'), // Expiration (in minutes)
            '/', // Path
            null, // Domain (null = default)
            true, // Secure (HTTPS only)
            true // HTTP-only
        )->cookie(
            'refresh_token', // Set the new refresh token as an HTTP-only cookie
            $tokens['refresh_token'], // The new refresh token value
            config('api.refresh_token_expiration'),
            null, // Path
            null, // Domain
            true, // Secure (HTTPS only)
            true // HTTP-only (not accessible via JavaScript)
        );
    }

    public function me(): JsonResponse
    {
        return ApiResponse::success(Auth::user(), 'User Login Successfully', 200);
    }

    public function validateToken(Request $request): JsonResponse
    {
        // Retrieve the bearer token from the request headers
        $accessToken = $request->bearerToken() ?? $request->cookie('access_token');

        // Check if the token is provided
        if (!$accessToken) {
            Log::warning('No token provided', ['ip' => $request->ip()]);
            return ApiResponse::error('No token provided', 401);
        }

        // Find the token in the database
        $tokenModel = PersonalAccessToken::findToken($accessToken);

        // Check if the token exists
        if (!$tokenModel) {
            Log::warning('Invalid token provided', ['ip' => $request->ip()]);
            return ApiResponse::error('Invalid token, the token does\'nt exists  ', 401);
        }

        // Get the user associated with the token
        /** @var \App\Models\User $user */
        $user = $tokenModel->tokenable;

        // Check if the token has expired
        if (Carbon::parse($tokenModel->expires_at)->isPast()) {
            Log::warning('Expired token used', ['user_id' => $tokenModel->tokenable->id]);
            // Revoke the expired token
            $tokenModel->delete();
            return ApiResponse::error('Token has expired. Please login again.', 401);
        }

        // Return a success response with the user and token details
        return ApiResponse::success($user, 'The token is valid', 200, [
            "access_token" => $accessToken, // Include the access token in the response
            "expires_at" => $tokenModel->expires_at, // Include the token's expiration time
        ]);
    }

    public function refreshToken(Request $request): JsonResponse
    {
        // Retrieve the refresh token from the HTTP-only cookie
        $refreshToken = $request->cookie('refresh_token');

        // Check if the refresh token is provided; if not, return an error response
        if (!$refreshToken) {
            return ApiResponse::error('Refresh token is missing', 401);
        }

        // Find the refresh token in the database using Laravel's built-in findToken method
        $tokenModel = PersonalAccessToken::findToken($refreshToken);

        // If no matching refresh token is found, return an error response
        if (!$tokenModel) {
            return ApiResponse::error('Invalid refresh token', 401);
        }

        // Retrieve the user associated with the refresh token
        /** @var \App\Models\User $user */
        $user = $tokenModel->tokenable;

        // Check if the refresh token has expired
        if (Carbon::parse($tokenModel->expires_at)->isPast()) {
            // If expired, revoke the token and return an error response
            $tokenModel->delete();
            return ApiResponse::error('Refresh token has expired. Please login again.', 401);
        }

        // Generate the current device fingerprint (IP + User-Agent) to prevent token theft
        $currentFingerprint = hash('sha256', $request->ip() . $request->userAgent());
        $storedFingerprint = $tokenModel->fingerprint ?? null;

        // If the fingerprint is missing or does not match, revoke the token and return an error response
        if (!$storedFingerprint || $storedFingerprint !== $currentFingerprint) {
            $tokenModel->delete(); // Revoke mismatched token
            return ApiResponse::error('Refresh token does not match device. Please login again.', 401);
        }

        // Refresh token rotation is a security practice where a new refresh token is issued each time a refresh token is used.
        $tokenModel->delete();
        $tokens = $this->authService->createTokens($request, $user, true, 'access_and_refresh_tokens');

        // // Generate new access and preserve the refresh token
        // $accessToken = $this->authService->createTokens($request, $user, true, 'access_token');

        // Log the token refresh event for security monitoring
        Log::info("User {$user->id} refreshed their token from IP: " . $request->ip() . " with User-Agent: " . $request->header('User-Agent'));

        // Return a success response containing the new access token and its expiration time
        return ApiResponse::success($user, 'Token Refreshed Successfully', 200, [
            "access_token" => $tokens['access_token'], // Include the new access token in the response
        ])->cookie(
            'refresh_token', // Set the new refresh token as an HTTP-only cookie
            $tokens['refresh_token'], //$refreshToken,  
            config('api.refresh_token_expiration'), // Expiration time for the refresh token
            null, // Path (defaults to "/")
            null, // Domain (defaults to current domain)
            true, // Secure (HTTPS only)
            true // HTTP-only (not accessible via JavaScript for security reasons)
        );
    }

    public function reAuthenticate(Request $request): JsonResponse
    {
        // Validate the incoming request data to ensure a strong password is provided
        $validator = Validator::make($request->all(), [
            'password' => ['required', new StrongPassword()], // Enforces strong password policy
        ]);

        // If validation fails, return an error response with validation errors
        if ($validator->fails()) {
            return ApiResponse::error('Validation Error', 400, $validator->errors());
        }

        /** @var \App\Models\User $user */
        // Retrieve the currently authenticated user
        $user = Auth::user();

        // Verify if the provided password matches the stored hashed password
        if (!Hash::check($request->password, $user->password)) {
            return ApiResponse::error('Invalid password. Reauthentication failed.', 403);
        }

        // Revoke any existing re-authentication token to prevent multiple active tokens
        $user->tokens()->where('name', 'reauth_token')->delete();

        // Generate a new re-authentication token with a 10-minute expiration
        $reauthToken = $this->authService->createTokens($request, $user, true, 'reauth_token');

        // Return the newly generated re-authentication token along with its expiration time
        return ApiResponse::success([
            'reauth_token' => $reauthToken, // Short-lived token
        ], 'Re-authentication successful');
    }

    public function logoutFromCurrentSession(Request $request): JsonResponse
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
        return ApiResponse::success([], 'Successfully logged out.', 200)
            ->withoutCookie('refresh_token');
    }

    public function logoutFromAllDevicesExceptCurrentSession(Request $request): JsonResponse
    {
        // Get the authenticated user
        $user = $request->user();

        // Get the current access token ID
        $currentAccessTokenId = $user->currentAccessToken()->id;

        // Get the refresh token from the cookie
        $refreshToken = $request->cookie('refresh_token');

        // Find the refresh token in the database
        $refreshTokenModel = PersonalAccessToken::findToken($refreshToken);

        // Get the refresh token ID (if it exists)
        $refreshTokenId = $refreshTokenModel ? $refreshTokenModel->id : null;

        // Delete all tokens except the current access token and refresh token
        $user->tokens()
            ->where('id', '!=', $currentAccessTokenId) // Exclude current access token
            ->when($refreshTokenId, function ($query) use ($refreshTokenId) {
                return $query->where('id', '!=', $refreshTokenId); // Exclude refresh token
            })
            ->delete();

        return ApiResponse::success([], 'Successfully logged out from all other devices but still logged in on the current device', 200);
    }

    public function logoutFromAllDevices(Request $request): JsonResponse
    {
        // Ensure the user is authenticated
        if (!$request->user()) {
            return ApiResponse::error('Unauthorized', 401);
        }

        // Get the authenticated user
        $user = $request->user();

        // Revoke all tokens (access and refresh tokens)
        $deletedCount = $user->tokens()->delete();

        // Log the action
        Log::info('User logged out from all devices', [
            'user_id' => $user->id,
            'deleted_count' => $deletedCount,
            'ip' => $request->ip(),
        ]);

        // Clear the refresh token cookie
        return ApiResponse::success([], 'Successfully logged out from all devices (sessions).', 200)
            ->withoutCookie('refresh_token');
    }

    public function logoutFromSpecificDevice(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $request->validate([
                'ip' => 'required|string',
                'user_agent' => 'required|string',
            ]);

            // Get the authenticated user
            $user = $request->user();

            // Find and delete tokens matching the encrypted fingerprint
            $deletedTokens = $user->tokens()
                ->where('fingerprint', $request->input('ip') . '|' . $request->input('user_agent'))
                ->delete();

            // Check if any tokens were deleted
            if ($deletedTokens > 0) {
                return ApiResponse::success([], 'Successfully logged out from the device (session).', 200);
            } else {
                return ApiResponse::error('No matching tokens found for the provided fingerprint.', 404);
            }
        } catch (\Exception $e) {
            // Log the error
            Log::error('Failed to logout from specific device:', [
                'error' => $e->getMessage(),
            ]);

            // Return an error response
            return ApiResponse::error('Failed to logout from the device.', 500);
        }
    }

    public function listAllUserTokens(Request $request): JsonResponse
    {
        try {
            // Retrieve the authenticated user
            $user = $request->user();

            // Retrieve all tokens for the user
            $allTokens = $user->tokens()->get();

            // Helper function to filter and map tokens
            $filterTokens = function ($tokens, $name) {
                return $tokens->filter(function ($token) use ($name) {
                    return $token->name == $name;
                })->map(function ($token) {
                    return [
                        'name' => $token->name,
                        'token' => $token->token,
                        'abilities' => $token->abilities,
                    ];
                });
            };

            // Filter tokens by type
            $access_tokens = $filterTokens($allTokens, 'access_token');
            $refresh_tokens = $filterTokens($allTokens, 'refresh_token');
            $reauth_tokens = $filterTokens($allTokens, 'reauth_token');

            // Combine token types
            $tokens = [
                'access_tokens' => $access_tokens,
                'refresh_tokens' => $refresh_tokens,
                'reauth_tokens' => $reauth_tokens,
            ];

            // Return the response
            return ApiResponse::success($tokens, 'Tokens retrieved successfully.', 200);
        } catch (\Exception $e) {
            // Handle errors gracefully
            return ApiResponse::error('Failed to retrieve tokens.', 500);
        }
    }
    public function listAllDevicesOfUserHasTokens(Request $request)
    {
        try {
            // Retrieve all tokens for the authenticated user
            $tokens = $request->user()->tokens()->get();

            // Extract unique fingerprints
            $devices = $tokens->map(function ($token) {
                // Ensure the fingerprint is set and not empty
                if (empty($token->fingerprint)) {
                    return null;
                }

                try {

                    // Decrypt the fingerprint
                    // $decryptedFingerprint = Crypt::decryptString($token->fingerprint);

                    // Split the fingerprint into IP and user agent
                    $fingerprintParts = explode('|', $token->fingerprint);

                    return [
                        'device_fingerprint' => [
                            'ip' => $fingerprintParts[0] ?? null,
                            'user_agent' => $fingerprintParts[1] ?? null,
                        ]
                    ];
                } catch (\Exception $e) {
                    // Log decryption errors
                    Log::error('Failed to process fingerprint for token ID ' . $token->id, [
                        'error' => $e->getMessage(),
                    ]);
                    return null;
                }
            })
                ->filter() // Remove null values (invalid fingerprints)
                ->unique(function ($device) {
                    return $device['device_fingerprint']['ip'] . '|' . $device['device_fingerprint']['user_agent'];
                }) // Ensure uniqueness
                ->values(); // Reset array keys

            // Return response
            return ApiResponse::success($devices, 'Devices retrieved successfully.', 200);
        } catch (\Exception $e) {
            // Log general errors
            Log::error('Failed to retrieve devices:', [
                'error' => $e->getMessage(),
            ]);

            // Return an error response
            return ApiResponse::error('Failed to retrieve devices.', 500);
        }
    }
}