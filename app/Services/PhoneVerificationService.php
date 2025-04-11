<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use RuntimeException;


class PhoneVerificationService
{
    public ?object $mobile;
    public function __construct(public Request $request)
    {
        $this->mobile = DB::table('phones')
            ->where('user_id', $this->request->user()->id)
            // ->where('phone', $this->request->phone)
            ->where('is_primary', true)
            ->first();
    }

    public function sendCodeVerificationMessage($user = null, $token = null): ?string
    {
        try {
            // If no user or token provided, fetch from the request
            if (!$user) {
                $user = $this->request->user();
                $token = $this->request->bearerToken();
            }

            // Ensure the user exists
            if (!$user || !$token) {
                Log::warning("Failed to send verification message: User or token missing.");
                // return null;
                throw new RuntimeException("Failed to send verification message: User or token missing.", 400);
            }
            
            // Generate the verification link
            $verificationUrl = $this->getVerificationLinkFromFrontEnd();
            if (!$verificationUrl) {
                Log::warning("Verification link is missing in the request.");
                // return null;
                throw new RuntimeException("Verification link is missing in the request.");
            }
            
            // Append the token to the verification link
            $verificationUrl .= "?token=$token";
            
            // Send verification message using : tailwand
            $this->sendSmsHelper($this->mobile?->phone, $verificationUrl);
            
            Log::info("Verification message sent successfully to {$user->email}.");

            return $verificationUrl;
        } catch (\Exception $e) {
            Log::error("Error sending verification email: " . $e->getMessage());
            // return null;
            throw new RuntimeException($e->getMessage());
        }
    }

    private function getVerificationLinkFromFrontEnd(): ?string
    {
        $validator = Validator::make($this->request->all(), [
            "verification_link" => "required|string|url",
        ]);

        if ($validator->fails()) {
            Log::warning("Invalid verification link provided.", $validator->errors()->toArray());
            return null;
        }

        return $this->request->input('verification_link');
    }

    public function sendActivationMessage(): bool
    {
        try {
            $this->sendSmsHelper($this->mobile?->phone, 'your phone is verified');
            return true;
        } catch (\Exception $e) {
            Log::error("Error sending activation message: " . $e->getMessage());
            return false;
        }
    }

    public function sendSmsHelper(string $mobile, string $message): void
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

    public function activatePhone(string|int $phoneID): bool
    {
        // Activate phone
        return (bool) DB::table('phones')->where('id', $phoneID)->update([
            'phone_verified_at' => now(),
            'phone_verification_code' => null,
            'phone_verification_expires_at' => null,
        ]);
    }
}