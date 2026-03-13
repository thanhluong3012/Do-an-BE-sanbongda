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
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\TournamentController;
use App\Http\Controllers\Api\TournamentRegistrationController;
use App\Http\Controllers\Api\TournamentMatchController;


// ==========================================
// ROUTES CÔNG KHAI (không cần đăng nhập)
// ==========================================

// Authentication Routes
Route::prefix('auth')->group(function () {
    // Đăng ký: POST /api/auth/register
    Route::post('register', [AuthController::class, 'register']);

    // Đăng nhập: POST /api/auth/login
    Route::post('login', [AuthController::class, 'login']);
});

// Đăng ký làm chủ sân - Public (không cần đăng nhập)
Route::post('owner-registrations', [OwnerRegistrationController::class, 'store']);

// Public Routes - Xem thông tin sân cho khách (không cần đăng nhập)
Route::prefix('public')->group(function () {
    // Cụm sân
    Route::get('field-clusters', [FieldClusterController::class, 'publicIndex']);
    Route::get('field-clusters/{id}', [FieldClusterController::class, 'publicShow']);

    // Sân
    Route::get('fields', [FieldController::class, 'publicIndex']);
    Route::get('fields/{id}', [FieldController::class, 'publicShow']);

    // Giá sân theo giờ
    Route::get('field-hourly-prices', [FieldHourlyPriceController::class, 'publicIndex']);
    Route::get('field-hourly-prices/{id}', [FieldHourlyPriceController::class, 'publicShow']);
});


// ==========================================
// ROUTES CẦN XÁC THỰC (jwt.auth middleware)
// ==========================================

Route::middleware('jwt.auth')->group(function () {

    // ==========================================
    // Auth Routes - Dành cho user đã đăng nhập
    // ==========================================
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // ==========================================
    // User & Role Management - CHỈ ADMIN
    // ==========================================
    Route::middleware('check.role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::apiResource('roles', RoleController::class);
    });

    // ==========================================
    // Owner Registration Management - CHỈ ADMIN
    // ==========================================
    Route::prefix('admin')->middleware('check.role:admin')->group(function () {
        Route::get('owner-registrations', [OwnerRegistrationController::class, 'index']);
        Route::get('owner-registrations/{id}', [OwnerRegistrationController::class, 'show']);
        Route::put('owner-registrations/{id}/approve', [OwnerRegistrationController::class, 'approve']);
        Route::put('owner-registrations/{id}/reject', [OwnerRegistrationController::class, 'reject']);
        Route::delete('owner-registrations/{id}', [OwnerRegistrationController::class, 'destroy']);
    });

    // ==========================================
    // Owner & Staff Management - ADMIN & OWNER ONLY
    // ==========================================
    Route::middleware('check.role:admin,owner')->group(function () {
        // Quản lý chủ sân
        Route::apiResource('owners', OwnerController::class);

        // Quản lý nhân viên
        Route::apiResource('staffs', StaffController::class);

        // QUẢN LÝ Cụm sân (Tạo, Sửa, Xóa)
        Route::post('field-clusters', [FieldClusterController::class, 'store']);
        Route::put('field-clusters/{id}', [FieldClusterController::class, 'update']);
        Route::delete('field-clusters/{id}', [FieldClusterController::class, 'destroy']);

        // QUẢN LÝ Sân (Tạo, Sửa, Xóa)
        Route::post('fields', [FieldController::class, 'store']);
        Route::put('fields/{id}', [FieldController::class, 'update']);
        Route::delete('fields/{id}', [FieldController::class, 'destroy']);

        // QUẢN LÝ Giá theo giờ (Tạo, Sửa, Xóa)
        Route::post('field-hourly-prices', [FieldHourlyPriceController::class, 'store']);
        Route::put('field-hourly-prices/{id}', [FieldHourlyPriceController::class, 'update']);
        Route::delete('field-hourly-prices/{id}', [FieldHourlyPriceController::class, 'destroy']);
    });

    // ==========================================
    // Field Information - TẤT CẢ USER ĐÃ ĐĂNG NHẬP (Chỉ xem)
    // ==========================================
    // Xem cụm sân
    Route::get('field-clusters', [FieldClusterController::class, 'index']);
    Route::get('field-clusters/{id}', [FieldClusterController::class, 'show']);

    // Xem sân
    Route::get('fields', [FieldController::class, 'index']);
    Route::get('fields/{id}', [FieldController::class, 'show']);

    // Xem giá theo giờ
    Route::get('field-hourly-prices', [FieldHourlyPriceController::class, 'index']);
    Route::get('field-hourly-prices/{id}', [FieldHourlyPriceController::class, 'show']);

    // ==========================================
    // Booking (Đặt sân) - Tất cả user đã đăng nhập
    // ==========================================
    Route::apiResource('books', BookController::class);

    // ==========================================
    // Team (Đội bóng) - Tất cả user đã đăng nhập
    // ==========================================
    Route::apiResource('teams', TeamController::class);

    // ==========================================
    // Tournament (Giải đấu) - Tất cả user đã đăng nhập
    // ==========================================
    Route::apiResource('tournaments', TournamentController::class);

    // Routes đặc biệt cho giải đấu
    Route::post('tournaments/{id}/publish', [TournamentController::class, 'publish']);

    // ==========================================
    // Tournament Registration (Đăng ký giải đấu) - Tất cả user đã đăng nhập
    // ==========================================
    Route::apiResource('tournament-registrations', TournamentRegistrationController::class);

    // Routes đặc biệt cho đăng ký giải đấu
    Route::put('tournament-registrations/{id}/approve', [TournamentRegistrationController::class, 'approve']);
    Route::put('tournament-registrations/{id}/reject', [TournamentRegistrationController::class, 'reject']);

    // ==========================================
    // Tournament Match (Trận đấu) - Tất cả user đã đăng nhập
    // ==========================================
    Route::apiResource('tournament-matches', TournamentMatchController::class);

    // Routes đặc biệt cho trận đấu
    Route::put('tournament-matches/{id}/update-score', [TournamentMatchController::class, 'updateScore']);
});
                         