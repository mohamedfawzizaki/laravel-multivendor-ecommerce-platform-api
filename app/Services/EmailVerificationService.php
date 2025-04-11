<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Mail\ActivationEmail;
use App\Mail\VerificationEmail;
use App\Mail\VerificationCodeEmail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;


class EmailVerificationService
{
    public function __construct(public Request $request) {}

    public function sendVerificationEmail($user = null, $token = null): ?string
    {
        try {
            // If no user or token provided, fetch from the request
            if (!$user) {
                $user = $this->request->user();
                $token = $this->request->bearerToken();
            }

            // Ensure the user exists
            if (!$user || !$token) {
                Log::warning("Failed to send verification email: User or token missing.");
                return null;
            }

            // Generate the verification link
            $verificationUrl = $this->getVerificationLinkFromFrontEnd();
            if (!$verificationUrl) {
                Log::warning("Verification link is missing in the request.");
                return null;
            }

            // Append the token to the verification link
            $verificationUrl .= "?token=$token";

            // Send verification email using SMTP
            Mail::mailer('smtp')->to($user->email)->locale('ar')->send(new VerificationEmail($user, $verificationUrl));

            Log::info("Verification email sent successfully to {$user->email}.");

            return $verificationUrl;
        } catch (\Exception $e) {
            Log::error("Error sending verification email: " . $e->getMessage());
            return null;
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

    public function sendActivationEmail(): bool
    {
        try {
            $user = $this->request->user();
            if (!$user) {
                Log::warning("Failed to send activation email: No authenticated user.");
                return false;
            }

            Mail::mailer('smtp')->to($user->email)->send(new ActivationEmail($user));

            Log::info("Activation email sent successfully to {$user->email}.");

            return true;
        } catch (\Exception $e) {
            Log::error("Error sending activation email: " . $e->getMessage());
            return false;
        }
    }

    public function sendVerificationCodeEmail(string $email, string $message): void
    {
        try {
            Mail::mailer('smtp')->to($email)->send(new VerificationCodeEmail($message));
        } catch (\Exception $e) {
            Log::error("Error sending activation email: " . $e->getMessage());
        }
    }
}