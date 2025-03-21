<?php

namespace App\Http\Controllers\Api\Auth;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Validator;
use App\Services\EmailVerificationService;

class EmailVerificationController extends Controller
{
     /**
     * Resend the email verification notification.
     *
     * This method triggers a new email verification process and returns the generated verification link.
     *
     * @param Request $request The HTTP request instance.
     * @return JsonResponse The API response containing the verification link.
     */
    public function resendVerificationEmail(Request $request): JsonResponse
    {
        try {
            // Initialize email verification service
            $emailVerificationService = new EmailVerificationService($request);

            // Send verification email and retrieve the verification link
            $verificationUrl = $emailVerificationService->sendVerificationEmail();

            // Log email verification resend attempt
            Log::info('Email verification resent successfully', [
                'user_id' => Auth::id(),
                'email' => Auth::user()->email,
                'verification_link' => $verificationUrl,
            ]);

            return ApiResponse::success([
                "verification_link" => $verificationUrl,
            ], 'Verification email sent successfully.', 200);
        } catch (Exception $e) {
            Log::error('Failed to resend email verification', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error('Failed to resend verification email.', 500);
        }
    }
    /**
     * Send a mobile verification code via SMS.
     *
     * This method generates a secure one-time verification code and sends it to the user's mobile number.
     * It ensures the user is authenticated, validates the mobile number, and prevents frequent resending of codes.
     * The verification code is securely stored in the database and sent to the user via SMS.
     *
     * @param Request $request The HTTP request instance containing the user's mobile number.
     * @return JsonResponse The API response indicating success or failure.
     *
     * @throws \Illuminate\Validation\ValidationException If validation fails.
     * @throws \Exception If an unexpected error occurs during the process.
     */
    public function sendVerificationCode(Request $request): JsonResponse
    {
        // Ensure the user is authenticated
        if (!Auth::check()) {
            return ApiResponse::error('Unauthorized', 401); // Return error if the user is not authenticated
        }

        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|string|regex:/^\+?[0-9]{10,15}$/', // Supports optional '+' and ensures 10-15 digits
        ]);

        // If validation fails, return an error response with validation errors
        if ($validator->fails()) {
            return ApiResponse::error('Validation Failed', 422, $validator->errors());
        }

        try {
            /** @var \App\Models\User $user */
            $user = Auth::user(); // Get the authenticated user
            $mobile = $validator->validated()['mobile']; // Extract the validated mobile number

            // Prevent resending too frequently
            if ($user->email_verification_expires_at && Carbon::parse($user->email_verification_expires_at)->gt(Carbon::now())) {
                return ApiResponse::error('Please wait before requesting a new code.', 429); // Return error if the user requests a new code too soon
            }

            // Generate a secure random verification code
            $verificationCode = random_int(100000, 999999); // 6-digit code for better security

            // Set expiration time (5 minutes from now)
            $expiresAt = Carbon::now()->addMinutes(5);

            // Save code & expiration time in the database
            $user->email_verification_code = Hash::make($verificationCode); // Hash the verification code before storing
            $user->email_verification_expires_at = $expiresAt;
            $user->save();

            // Log the verification request
            Log::info('Verification code generated', [
                'user_id' => $user->id, // Log the user ID
                'mobile' => $mobile, // Log the mobile number
                'expires_at' => $expiresAt, // Log the expiration time
                'ip' => $request->ip(), // Log the user's IP address
            ]);

            // Send verification code (Replace with actual SMS sending logic)
            $this->sendSmsHelper($mobile, "Your verification code is: $verificationCode");

            // Return a success response
            return ApiResponse::success([], 'Verification code sent successfully.', 200);
        } catch (Exception $e) {
            // Log the error for debugging
            Log::error('Failed to send verification code', [
                'user_id' => Auth::id(), // Log the user ID
                'error' => $e->getMessage(), // Log the error message
            ]);

            // Return an error response
            return ApiResponse::error('Failed to send verification code', 500, ['error' => 'An unexpected error occurred.']);
        }
    }

    /**
     * Sends an SMS using Twilio.
     *
     * This function integrates with Twilio to send an SMS to the specified mobile number.
     * If an actual SMS provider is not set up, it logs the message instead.
     *
     * @param string $mobile The recipient's mobile number.
     * @param string $message The message to be sent.
     * @return void
     */
    private function sendSmsHelper(string $mobile, string $message): void
    {
        // try {
        //     // Ensure Twilio is configured in the environment file
        //     $sid = env('TWILIO_SID');
        //     $token = env('TWILIO_AUTH_TOKEN');
        //     $from = env('TWILIO_FROM');

        //     if (!$sid || !$token || !$from) {
        //         throw new Exception("Twilio credentials are missing in .env file.");
        //     }

        //     // Initialize Twilio client
        //     $client = new \Twilio\Rest\Client($sid, $token);

        //     // Send SMS
        //     $client->messages->create(
        //         $mobile, // Destination number
        //         [
        //             'from' => $from,
        //             'body' => $message
        //         ]
        //     );

        Log::info("SMS successfully sent to $mobile");
        // } catch (Exception $e) {
        //     // Log error for debugging
        //     Log::error("Failed to send SMS to $mobile", ['error' => $e->getMessage()]);
        // }
    }

    /**
     * Verify a verification code to activate a user's account.
     *
     * @param Request $request The HTTP request containing the verification code.
     * @return JsonResponse The API response indicating success or failure.
     */
    public function verifyCodeAndActivateEmail(Request $request): JsonResponse
    {
        // Validate the request input
        $validatedData = $request->validate([
            'code' => 'required|string|size:6', // Ensure exactly 6 characters
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        // Ensure the user has a verification code stored
        if (!$user->email_verification_code) {
            return ApiResponse::error('No verification code found. Please request a new one.', 400);
        }

        // Check if the verification code is expired
        if ($user->email_verification_expires_at && now()->greaterThan($user->email_verification_expires_at)) {
            return ApiResponse::error('Verification code has expired. Please request a new one.', 410);
        }

        // Verify the code securely using Hash::check()
        if (!Hash::check($validatedData['code'], $user->email_verification_code)) {
            // Log failed attempts for security monitoring
            Log::warning('Failed verification attempt', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);

            return ApiResponse::error('Invalid verification code.', 422);
        }

        // Activate the user account
        $user->email_verified_at = now();
        $user->email_verification_code = null;
        $user->email_verification_expires_at = null;
        $user->save();

        // Log successful activation
        Log::info('User account verified', ['user_id' => $user->id]);

        // Send activation email (consider queuing it for better performance)
        try {
            (new EmailVerificationService($request))->sendActivationEmail();
        } catch (Exception $e) {
            Log::error('Email verification notification failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        return ApiResponse::success([], 'Your account has been activated successfully.', 200);
    }

    /**
     * Check if the authenticated user's email is verified.
     *
     * This method returns a success response if the user's email is verified,
     * otherwise, it returns an error message indicating the email is not verified.
     *
     * @return JsonResponse The API response indicating the email verification status.
     */
    public function checkEmailIsVerified(): JsonResponse
    {
        $user = Auth::user();

        if ($user->email_verified_at) {
            Log::info('User email is verified', ['user_id' => $user->id, 'email' => $user->email]);
            return ApiResponse::success([], 'Email is verified', 200);
        }

        Log::warning('User email is not verified', ['user_id' => $user->id, 'email' => $user->email]);
        return ApiResponse::error('Email is not verified', 200);
    }
}