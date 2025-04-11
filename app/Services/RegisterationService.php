<?php

namespace App\Services;

use App\Models\Role;
use Carbon\Carbon;
use RuntimeException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegisterationService
{
    public function __construct(public UserService $userService) {}
    public function registerUser($request): array
    {
        return DB::transaction(function () use ($request) {
            $role = Role::whereName($request->input('role'))->first();
            if (!$role) {
                throw new RuntimeException('Invalid role provided.', 400);
            }

            $data = [
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => $request->input('password'),
                'role_id' => $role->id,
            ];

            $user = $this->userService->create($data);
            if (!$user) {
                Log::error('User registration failed: User creation service returned null.');
                throw new RuntimeException('User creation failed.', 400);
            }

            // Assign Address
            $cityID = $request->input('city_id');
            $cityName = $request->input('city_name');
            if ($cityID || $cityName) {
                $result = $cityID
                    ? $this->userService->assignAddressByCityID($cityID, $user->id)
                    : $this->userService->assignAddressByCityName($cityName, $user->id);

                if (!$result) {
                    throw new RuntimeException('Assigning address to the user failed.', 400);
                }
            }

            $phoneData = [
                'user_id' => $user->id,
                'phone' => $request->input('phone'),
                'is_primary' => $request->boolean('is_primary', false),
            ];

            $phone = $this->createPhone($phoneData);
            // send verification code to user

            if (!$phone) {
                Log::error('User registration failed: Phone creation service returned null.');
                throw new RuntimeException('Assigning Phone failed.', 400);
            }

            // Generate authentication tokens (access & refresh)
            // $authService = new AuthService();
            // $tokens = $authService->createTokens($request, $user, true);
            $tokens = app(AuthService::class)->createTokens($request, $user, true);

            // Send email verification notification
            // $emailVerificationService = new EmailVerificationService($request);
            // $verificationUrl = $emailVerificationService->sendVerificationEmail($user, $tokens['access_token']);
            $verificationUrl = app(EmailVerificationService::class)
                ->sendVerificationEmail($user, $tokens['access_token']);

            // Ensure the verification link was generated successfully
            if (!$verificationUrl) {
                Log::warning("Verification email failed to send to {$user->email}");
            }

            // Send phone verification notification

            return [
                'user' => $this->userService->getUserById($user->id),
                'tokens' => $tokens,
                'verification_url' => $verificationUrl,
            ];
        });
    }
    public function createPhone($data)
    {
        // Insert phone and get ID
        $phoneId = DB::table('phones')->insertGetId([
            'user_id' => $data['user_id'],
            'phone' => $data['phone'],
            'is_primary' => $data['is_primary'],
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        if (!$phoneId) {
            return null;
        }

        // Fetch the inserted phone record
        $phone = DB::table('phones')->where('id', $phoneId)->first();

        return $phone;
    }
}