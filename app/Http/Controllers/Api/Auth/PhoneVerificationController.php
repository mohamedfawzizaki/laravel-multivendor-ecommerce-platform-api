<?php

namespace App\Http\Controllers\Api\Auth;

use Exception;
use Carbon\Carbon;
use RuntimeException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\StorePhoneRequest;
use Illuminate\Support\Facades\Validator;
use App\Services\PhoneVerificationService;
use App\Http\Requests\ValidateColumnAndConditionRequest;

class PhoneVerificationController extends Controller
{
    public function __construct(public PhoneVerificationService $phoneVerificationService) {}

    public function registerPhone(StorePhoneRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $validated = $request->validated();
                $userId = $request->user()->id;

                // If the phone is primary, ensure the user does not already have one
                if ($validated['is_primary']) {
                    $existingPrimary = DB::table('phones')
                        ->where('user_id', $userId)
                        ->where('is_primary', true)
                        ->exists();

                    if ($existingPrimary) {
                        throw new RuntimeException("User already has a primary phone.", 400);
                    }
                }

                // Insert new phone
                $phoneId = DB::table('phones')->insertGetId([
                    'phone' => $validated['phone'],
                    'is_primary' => $validated['is_primary'],
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Fetch the newly created phone record
                $phone = DB::table('phones')->where('id', $phoneId)->first();

                return ApiResponse::success($phone, 'Phone registered successfully.');
            });
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error creating phone: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function myPhones(string $id): JsonResponse
    {
        try {
            $phones = DB::table('phones')->where('user_id', $id)->get();
            return ApiResponse::success($phones, 'Phones retrieved successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone' => 'sometimes|string|unique:phones,phone|max:30',
                'is_primary' => 'sometimes|boolean',
                'columns'  => 'sometimes|array',
            ]);

            if ($validator->fails()) {
                Log::warning("Phone updating validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }
            
            $validatedData = $request->except(['columns']);
            $columns = $request->only(['columns']) ?? ['*'];

            $updates = DB::table('phones')->where('id', $id)->update($validatedData);


            if(!($updates > 0)) {
                throw new RuntimeException('Error updating phone');
            }
            
            $phone = DB::table('phones')->where('id', $id)->first();
            return ApiResponse::success($phone, 'Phone updated successfully.');
            // Fetch the updated phone record
        } catch (Exception $e) {
            Log::error("Error updating phone: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function resendPhoneVerification(Request $request): JsonResponse
    {
        try {
            if (!$this->phoneVerificationService->mobile) {
                return ApiResponse::error('Register your phone first');
            }
            // Send verification message and retrieve the verification link
            $verificationUrl = $this->phoneVerificationService->sendCodeVerificationMessage();

            // Log email verification resend attempt
            Log::info('Phone verification resent successfully', [
                'user_id' => Auth::id(),
                'verification_link' => $verificationUrl,
            ]);

            return ApiResponse::success([
                "verification_link" => $verificationUrl,
            ], 'Verification message sent successfully.', 200);
        } catch (Exception $e) {
            Log::error('Failed to resend phone verification', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error('Failed to resend verification message.' . $e->getMessage(), 500);
        }
    }

    public function sendVerificationCode(Request $request): JsonResponse
    {
        // using mobile:
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

            // Generate a secure random verification code
            $verificationCode = random_int(100000, 999999); // 6-digit code for better security

            // Set expiration time (5 minutes from now)
            $expiresAt = Carbon::now()->addMinutes(5);

            $mobile = $validator->validated()['mobile'];

            // Save code & expiration time in the database
            $updates = DB::table('phones')->where('phone', $mobile)->update([
                'phone_verification_code' => $verificationCode, //Hash::make($verificationCode),
                'phone_verification_expires_at' => $expiresAt,
            ]);

            if (!($updates > 0)) {
                return ApiResponse::error('Failed to send verification code', 500, ['error' => 'the provided phone number is not registered']);
            }

            // Log the verification request
            Log::info('Verification code generated', [
                'user_id' => $user->id, // Log the user ID
                'expires_at' => $expiresAt, // Log the expiration time
                'ip' => $request->ip(), // Log the user's IP address
            ]);

            // Send verification code (Replace with actual SMS sending logic)
            $this->phoneVerificationService->sendSmsHelper($mobile, "Your verification code is: $verificationCode");

            return ApiResponse::success([
                'phone_verification_code' => $verificationCode,
                'phone_verification_expires_at' => $expiresAt
            ], 'Verification code sent successfully.', 200);
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

    public function verifyCodeAndActivatePhone(Request $request): JsonResponse
    {
        try {
            // Validate the request input
            $validatedData = $request->validate([
                'code' => 'required|string|size:6', // Ensure exactly 6 characters
            ]);

            /** @var \App\Models\User $user */
            $user = $request->user();

            // Ensure the user has a verification code stored
            if (!$this->phoneVerificationService->mobile->phone_verification_code) {
                return ApiResponse::error('No verification code found. Please request a new one.', 400);
            }

            // Check if the verification code is expired
            if ($this->phoneVerificationService->mobile->phone_verification_expires_at && now()->greaterThan($this->phoneVerificationService->mobile->phone_verification_expires_at)) {
                return ApiResponse::error('Verification code has expired. Please request a new one.', 410);
            }

            // Verify the code securely using Hash::check()
            if (!Hash::check($validatedData['code'], $this->phoneVerificationService->mobile->phone_verification_code)) {
                // Log failed attempts for security monitoring
                Log::warning('Failed verification attempt', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                ]);


                return ApiResponse::error('Invalid verification code.', 422);
            }

            // Activate the user phone
            $verified = $this->phoneVerificationService->activatePhone($this->phoneVerificationService->mobile->id);
            if (!$verified) {
                return ApiResponse::error('Failed to activate phone.', 500);
            }

            // Log successful activation
            Log::info('User Phone verified', ['user_id' => $user->id]);

            // Send activation message (consider queuing it for better performance)
            $this->phoneVerificationService->sendActivationMessage();

            return ApiResponse::success([], 'Your phone has been activated successfully.', 200);
        } catch (Exception $e) {
            Log::error('Email verification notification failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error('Failed to activate phone.', 500);
        }
    }

    public function checkPhoneIsVerified(): JsonResponse
    {
        if ($this->phoneVerificationService->mobile->phone_verified_at) {
            return ApiResponse::success([], 'Phone is verified', 200);
        }
        return ApiResponse::success([], 'Phone is not verified', 200);
    }
}