<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\RegistrationController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Auth\AccountManagementController;
use App\Http\Controllers\Api\Auth\EmailVerificationController;
use App\Http\Controllers\Api\Auth\PhoneVerificationController;

# Using Sanctum Package:
Route::group(["prefix" => "auth"], function () {

    Route::post('register', [RegistrationController::class, 'register']);
    #-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    # Login : 
    Route::post('login', [AuthController::class, 'login']);
    Route::get('me', [AuthController::class, 'me'])->middleware('auth:sanctum');
    # Validate the current access token:
    Route::post('token/validate', [AuthController::class, 'validateToken']); //->middleware('auth:sanctum');
    # Refresh token:
    Route::post('token/refresh', [AuthController::class, 'refreshToken']); //->middleware('auth:sanctum');
    # Re-Authentication token:
    Route::post('token/re-authenticate', [AuthController::class, 'reAuthenticate'])->middleware('auth:sanctum');
    # Logout:
    Route::post('logout', [AuthController::class, 'logoutFromCurrentSession'])->middleware('auth:sanctum');
    Route::post('logout/all-except-current', [AuthController::class, 'logoutFromAllDevicesExceptCurrentSession'])->middleware('auth:sanctum');
    Route::post('logout/all', [AuthController::class, 'logoutFromAllDevices'])->middleware('auth:sanctum');
    Route::post('logout/from-specific-device', [AuthController::class, 'logoutFromSpecificDevice'])->middleware('auth:sanctum');
    # List all tokens of the user:
    Route::get('tokens', [AuthController::class, 'listAllUserTokens'])->middleware('auth:sanctum');
    Route::get('tokens/devices', [AuthController::class, 'listAllDevicesOfUserHasTokens'])->middleware('auth:sanctum');

    # Email verification routes using custom functionality
    Route::post('email/resend-email',       [EmailVerificationController::class, 'resendVerificationEmail'])->middleware('auth:sanctum');
    Route::post('email/send-code',          [EmailVerificationController::class, 'sendVerificationCode'])->middleware('auth:sanctum');
    Route::post('email/verify',        [EmailVerificationController::class, 'verifyCodeAndActivateEmail'])->middleware('auth:sanctum');
    Route::get ('email/is-verified', [EmailVerificationController::class, 'checkEmailIsVerified'])->middleware('auth:sanctum');

    # Phone verification routes using custom functionality
    Route::post('phone/register-phone',       [PhoneVerificationController::class, 'registerPhone'])->middleware('auth:sanctum');
    Route::post('phone/my-phones/{id}',       [PhoneVerificationController::class, 'myPhones'])->middleware('auth:sanctum');
    Route::post('phone/update/{id}',       [PhoneVerificationController::class, 'update'])->middleware('auth:sanctum');
    Route::post('phone/resend-message',       [PhoneVerificationController::class, 'resendPhoneVerification'])->middleware('auth:sanctum');
    Route::post('phone/send-code',          [PhoneVerificationController::class, 'sendVerificationCode'])->middleware('auth:sanctum');
    Route::post('phone/verify',        [PhoneVerificationController::class, 'verifyCodeAndActivatePhone'])->middleware('auth:sanctum');
    Route::get ('phone/is-verified', [PhoneVerificationController::class, 'checkPhoneIsVerified'])->middleware('auth:sanctum');

    # Account management
    Route::post('account/change-username',       [AccountManagementController::class, 'changeUsername'])->middleware('auth:sanctum');
    Route::post('account/change-email',       [AccountManagementController::class, 'changeEmail'])->middleware('auth:sanctum');
    Route::post('account/change-password',       [AccountManagementController::class, 'changePassword'])->middleware('auth:sanctum');
    Route::post('account/deactivate-account', [AccountManagementController::class, 'deactivateAccount'])->middleware('auth:sanctum');
    Route::post('account/reactivate-account', [AccountManagementController::class, 'reactivateAccount'])->middleware('auth:sanctum');
    Route::post('account/delete-account', [AccountManagementController::class, 'deleteAccount'])->middleware('auth:sanctum');

    # Password Reset:
    Route::post('password/request-reset',       [PasswordResetController::class, 'requestReset']);
    Route::get('password/verify-reset-token',       [PasswordResetController::class, 'verifyToken']);
    Route::post('password/reset',       [PasswordResetController::class, 'resetPassword']);

    # implement 2FA    Authentication
    # implement social Authentication
    # implement Oauth2 Authentication 





});