<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\OwnerController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\OwnerRegistrationController;
use App\Http\Controllers\Api\FieldHourlyPriceController;
use App\Http\Controllers\Api\FieldClusterController;
use App\Http\Controllers\Api\FieldController;


Route::prefix('auth')->group(function () {
    // Đăng ký: POST /api/auth/register
    Route::post('register', [AuthController::class, 'register']);

    // Đăng nhập: POST /api/auth/login
    Route::post('login', [AuthController::class, 'login']);
});

// Đăng ký làm chủ sân - Public (không cần đăng nhập)
Route::post('owner-registrations', [OwnerRegistrationController::class, 'store']);


Route::middleware('jwt.auth')->group(function () {

    // Auth routes - Dành cho user đã đăng nhập
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // User & Role management - CHỈ ADMIN
    Route::middleware('check.role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::apiResource('roles', RoleController::class);
    });

    // Owner Registration management - CHỈ ADMIN
    Route::prefix('admin')->middleware('check.role:admin')->group(function () {
        Route::get('owner-registrations', [OwnerRegistrationController::class, 'index']);
        Route::get('owner-registrations/{id}', [OwnerRegistrationController::class, 'show']);
        Route::put('owner-registrations/{id}/approve', [OwnerRegistrationController::class, 'approve']);
        Route::put('owner-registrations/{id}/reject', [OwnerRegistrationController::class, 'reject']);
        Route::delete('owner-registrations/{id}', [OwnerRegistrationController::class, 'destroy']);
    });

    // Owner & Staff management - Admin và Owner
    Route::middleware('check.role:admin,owner')->group(function () {
        Route::apiResource('owners', OwnerController::class);
        Route::apiResource('staffs', StaffController::class);
        Route::apiResource('field-clusters', FieldClusterController::class);
        Route::apiResource('fields', FieldController::class);
        Route::apiResource('field-hourly-prices', FieldHourlyPriceController::class);
    });
});

