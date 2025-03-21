<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;;

use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\PermissionController;



Route::prefix('users')
    // ->middleware('auth:sanctum,admin')
    ->controller(UserController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::get('{id}', 'show');
        Route::get('search/where', action: 'searchBy');

        Route::post('/', 'store');

        Route::put('{id}', 'update');
        Route::put('update/bulk', 'updateBulk');

        Route::delete('{id}', 'delete');
        Route::delete('delete/bulk', 'deleteBulk');

        Route::get('delete/check', 'IsSoftDeleted');
        Route::post('restore/{id}/', 'restore');
        Route::post('restore/bulk/users', 'restoreBulk');
    });


Route::group(['prefix' => 'roles'], function () {
    Route::get('/', [RoleController::class, 'index']);
    Route::post('/', [RoleController::class, 'store']);
    Route::get('{id}', [RoleController::class, 'show']);
    Route::put('{id}', [RoleController::class, 'update']);
    Route::delete('{id}', [RoleController::class, 'delete']);
});

Route::group(['prefix' => 'permissions'], function () {
    Route::get('/', [PermissionController::class, 'index']);
    Route::post('/', [PermissionController::class, 'store']);
    Route::get('{id}', [PermissionController::class, 'show']);
    Route::put('{id}', [PermissionController::class, 'update']);
    Route::delete('{id}', [PermissionController::class, 'delete']);
});

Route::group(['prefix' => 'statuses'], function () {
    Route::get('/', [StatusController::class, 'index']);
    Route::post('/', [StatusController::class, 'store']);
    Route::get('{id}', [StatusController::class, 'show']);
    Route::put('{id}', [StatusController::class, 'update']);
    Route::delete('{id}', [StatusController::class, 'delete']);
});