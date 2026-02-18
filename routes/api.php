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

    // User management - CHỈ ADMIN
    Route::middleware('check.role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::apiResource('roles', RoleController::class);

        // Quản lý yêu cầu đăng ký chủ sân
        Route::get('owner-registrations', [OwnerRegistrationController::class, 'index']);
        Route::get('owner-registrations/{id}', [OwnerRegistrationController::class, 'show']);
        Route::post('owner-registrations/{id}/approve', [OwnerRegistrationController::class, 'approve']);
        Route::post('owner-registrations/{id}/reject', [OwnerRegistrationController::class, 'reject']);
        Route::delete('owner-registrations/{id}', [OwnerRegistrationController::class, 'destroy']);
    });

    // Owner & Staff management - Admin và Owner
    Route::middleware('check.role:admin,owner')->group(function () {
        Route::apiResource('owners', OwnerController::class);
        Route::apiResource('staffs', StaffController::class);
    });
});

