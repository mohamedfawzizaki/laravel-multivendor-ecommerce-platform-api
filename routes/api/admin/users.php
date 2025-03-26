<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\User\PhoneController;
use App\Http\Controllers\Api\Admin\StatusController;
use App\Http\Controllers\Api\Admin\PermissionController;
use App\Http\Controllers\Api\Admin\RolePermissionController;



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
        Route::get('delete/check/{id}', 'isSoftDeleted');
        Route::post('{id}', 'restore');
        Route::post('restore/bulk/users', 'restoreBulk');
    });
    
    
    Route::group(['prefix' => 'statuses'], function () {
        Route::get('/', [StatusController::class, 'index']);
        Route::post('/', [StatusController::class, 'store']);
        Route::get('{id}', [StatusController::class, 'show']);
        Route::put('{id}', [StatusController::class, 'update']);
        Route::delete('{id}', [StatusController::class, 'delete']);
        Route::get('deleted/{id}', [StatusController::class, 'isSoftDeleted']);
        Route::post('{id}', [StatusController::class, 'restore']);
    });


    Route::group(['prefix' => 'roles'], function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::post('/', [RoleController::class, 'store']);
        Route::get('{id}', [RoleController::class, 'show']);
        Route::put('{id}', [RoleController::class, 'update']);
        Route::delete('{id}', [RoleController::class, 'delete']);
        Route::get('deleted/{id}', [RoleController::class, 'isSoftDeleted']);
        Route::post('{id}', [RoleController::class, 'restore']);
        
        Route::post('{role_id}/permissions/{permission_id}/id', [RolePermissionController::class, 'assignPermissionByID']);
        Route::post('{role_name}/permissions/{permission_name}/name', [RolePermissionController::class, 'assignPermissionByName']);
        Route::delete('{role_id}/permissions/{permission_id}/id', [RolePermissionController::class, 'removePermissionByID']);
        Route::delete('{role_name}/permissions/{permission_name}/name', [RolePermissionController::class, 'removePermissionByName']);
        
        Route::post('{role_name}/user/{id}', [RolePermissionController::class, 'assignRoleToUser']);
    });
    
    
    Route::group(['prefix' => 'permissions'], function () {
        Route::get('/', [PermissionController::class, 'index']);
        Route::post('/', [PermissionController::class, 'store']);
        Route::get('{id}', [PermissionController::class, 'show']);
        Route::put('{id}', [PermissionController::class, 'update']);
        Route::delete('{id}', [PermissionController::class, 'delete']);
        Route::get('deleted/{id}', [PermissionController::class, 'isSoftDeleted']);
        Route::post('{id}', [PermissionController::class, 'restore']);
    });
    
    
    Route::prefix('phones')
    // ->middleware('auth:sanctum,admin')
    ->controller(PhoneController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::get('{id}', 'show');
        Route::get('search/where', action: 'searchBy');
        Route::post('/', 'store');
        Route::put('{id}', 'update');
        Route::put('update/bulk', 'updateBulk');
        Route::delete('{id}', 'delete');
        Route::delete('delete/bulk', 'deleteBulk');
        Route::get('delete/check/{id}', 'isSoftDeleted');
        Route::post('{id}', 'restore');
        Route::post('restore/bulk/phones', 'restoreBulk');
    });



// Route::get('test', function () {
//     $role = Role::find(9);
//     // var_dump(DB::table('users')->get()->all());
//     return DB::table('users')->get()->all();
// });