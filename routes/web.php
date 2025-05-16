<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\Auth\EmailVerification\CustomEmailVerificationController;


Route::get('/', function () {
    return 'unatherized Access';
})->name('login');