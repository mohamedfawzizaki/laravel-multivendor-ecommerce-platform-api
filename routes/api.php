<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestController;

require __DIR__ . '/api/auth/auth.php';
require __DIR__ . '/api/users/users.php';



Route::get('test', [TestController::class, 'test']);

































// Route::middleware('auth:sanctum')->group(function () {
//     // User Routes
//     Route::apiResource('users', UserController::class);

//     // Role Routes
//     // Route::apiResource('roles', RoleController::class);

//     // Status Routes
//     // Route::apiResource('statuses', StatusController::class);
// });