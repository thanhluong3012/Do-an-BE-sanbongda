<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;

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

// ============================================
// PROTECTED ROUTES - Cần authentication
// Sử dụng middleware 'auth:api' để bảo vệ
// ============================================
Route::middleware('jwt.auth')->group(function () {

    // Auth routes - Các chức năng liên quan đến authentication
    Route::apiResource('users', UserController::class);
    Route::prefix('auth')->group(function () {
        // Đăng xuất: POST /api/auth/logout
        Route::post('logout', [AuthController::class, 'logout']);

        // Làm mới token: POST /api/auth/refresh
        Route::post('refresh', [AuthController::class, 'refresh']);

        // Lấy thông tin user: GET /api/auth/me
        Route::get('me', [AuthController::class, 'me']);

    });

    // User CRUD routes - Quản lý users
    // GET    /api/users       - Danh sách users
    // POST   /api/users       - Tạo user mới
    // GET    /api/users/{id}  - Chi tiết user
    // PUT    /api/users/{id}  - Cập nhật user
    // DELETE /api/users/{id}  - Xóa user

});

