<?php

namespace App\Services;

use App\Mail\ActivationEmail;
use App\Mail\VerificationEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;


class EmailVerificationService
{
    /**
     * The HTTP request instance.
     *
     * @var Request
     */
    public Request $request;

    /**
     * EmailVerificationService constructor.
     *
     * @param Request $request The HTTP request instance.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Sends a verification email with a unique verification link.
     *
     * @param mixed|null $user The user object (optional, will use authenticated user if null).
     * @param string|null $token The verification token (optional, will extract from the request if null).
     * @return string|null The generated verification URL or null in case of failure.
     */
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

    /**
     * Retrieves the frontend verification link from the request.
     *
     * @return string|null The verification link or null if validation fails.
     */
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

    /**
     * Sends an activation email to the user after successful verification.
     *
     * @return bool True if email was sent successfully, false otherwise.
     */
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
}