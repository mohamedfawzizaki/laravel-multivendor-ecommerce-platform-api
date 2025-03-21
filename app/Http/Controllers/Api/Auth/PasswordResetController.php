<?php

namespace App\Http\Controllers\Api\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Rules\StrongPassword;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Validator;
use App\Notifications\PasswordResetSuccessNotification;

class PasswordResetController extends Controller
{
    //
    public function requestReset(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation errors', 422, $validator->errors());
        }

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT ?
            ApiResponse::success([], 'Reset link sent successfully', 200, [__($status)]) :
            ApiResponse::error('Error sending reset link', 422, [__($status)]);
    }

    public function verifyToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:password_reset_tokens,email',
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation errors', 422, $validator->errors());
        }

        $tokenRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!Hash::check($request->token, $tokenRecord->token)) {
            return ApiResponse::error('Invalid token', 422);
        }
        return ApiResponse::success([], 'Token is valid', 200);
    }

    public function resetPassword(Request $request)
    {
        // ✅ Validate input with clear messages
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|email|exists:users,email',
            'password' => ['required', 'confirmed', new StrongPassword()],
        ], [
            'email.exists' => 'The email address is not registered.',
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', 422, $validator->errors());
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                // Fire password reset event
                event(new PasswordReset($user));

                // Optional: Send a notification (if applicable)
                $user->notify(new PasswordResetSuccessNotification());
            }
        );
        
        return $status === Password::PasswordReset ?
            ApiResponse::success([], 'Password has been reset successfully.', 200, [__($status)]) :
            ApiResponse::error('Error reseting password', 422, [__($status)]);
    }
}