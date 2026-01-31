<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\FieldController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Chia làm 2 nhóm routes:
| 1. PUBLIC ROUTES - Không cần token (register, login)
| 2. PROTECTED ROUTES - Cần token (logout, refresh, me, users CRUD)
|
*/

// ============================================
// PUBLIC ROUTES - Không cần authentication
// ============================================
Route::prefix('auth')->group(function () {
    // Đăng ký: POST /api/auth/register
    Route::post('register', [AuthController::class, 'register']);

    // Đăng nhập: POST /api/auth/login
    Route::post('login', [AuthController::class, 'login']);
});


Route::apiResource('fields', FieldController::class);

Route::middleware('jwt.auth')->group(function () {

    // Auth routes
    Route::apiResource('users', UserController::class);
    Route::apiResource('roles', RoleController::class);

    Route::prefix('auth')->group(function () {
        // Đăng xuất: POST /api/auth/logout
        Route::post('logout', [AuthController::class, 'logout']);

        // Làm mới token: POST /api/auth/refresh
        Route::post('refresh', [AuthController::class, 'refresh']);

        // Lấy thông tin user: GET /api/auth/me
        Route::get('me', [AuthController::class, 'me']);

    });
});

